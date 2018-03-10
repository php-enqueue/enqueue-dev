<?php

namespace Enqueue\JobQueue;

use Enqueue\Client\ProducerInterface;
use Enqueue\JobQueue\Doctrine\JobStorage;

class JobProcessor
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @param JobStorage        $jobStorage
     * @param ProducerInterface $producer
     */
    public function __construct(JobStorage $jobStorage, ProducerInterface $producer)
    {
        $this->jobStorage = $jobStorage;
        $this->producer = $producer;
    }

    /**
     * @param string $id
     *
     * @return Job
     */
    public function findJobById($id)
    {
        return $this->jobStorage->findJobById($id);
    }

    /**
     * @param string $ownerId
     * @param string $jobName
     * @param bool   $unique
     *
     * @return Job
     */
    public function findOrCreateRootJob($ownerId, $jobName, $unique = false)
    {
        if (!$ownerId) {
            throw new \LogicException('OwnerId must not be empty');
        }

        if (!$jobName) {
            throw new \LogicException('Job name must not be empty');
        }

        $job = $this->jobStorage->createJob();
        $job->setOwnerId($ownerId);
        $job->setStatus(Job::STATUS_NEW);
        $job->setName($jobName);
        $job->setCreatedAt(new \DateTime());
        $job->setStartedAt(new \DateTime());
        $job->setUnique((bool) $unique);

        try {
            $this->saveJob($job);

            return $job;
        } catch (DuplicateJobException $e) {
        }

        return $this->jobStorage->findRootJobByOwnerIdAndJobName($ownerId, $jobName);
    }

    /**
     * @param string $jobName
     * @param Job    $rootJob
     *
     * @return Job
     */
    public function findOrCreateChildJob($jobName, Job $rootJob)
    {
        if (!$jobName) {
            throw new \LogicException('Job name must not be empty');
        }

        $rootJob = $this->jobStorage->findJobById($rootJob->getId());
        $job = $this->jobStorage->findChildJobByName($jobName, $rootJob);

        if ($job) {
            return $job;
        }

        $job = $this->jobStorage->createJob();
        $job->setStatus(Job::STATUS_NEW);
        $job->setName($jobName);
        $job->setCreatedAt(new \DateTime());
        $job->setRootJob($rootJob);

        $this->saveJob($job);

        $this->sendCalculateRootJobStatusEvent($job);

        return $job;
    }

    /**
     * @param Job $job
     */
    public function startChildJob(Job $job)
    {
        if ($job->isRoot()) {
            throw new \LogicException(sprintf('Can\'t start root jobs. id: "%s"', $job->getId()));
        }

        $job = $this->jobStorage->findJobById($job->getId());

        if (Job::STATUS_NEW !== $job->getStatus()) {
            throw new \LogicException(sprintf(
                'Can start only new jobs: id: "%s", status: "%s"',
                $job->getId(),
                $job->getStatus()
            ));
        }

        $job->setStatus(Job::STATUS_RUNNING);
        $job->setStartedAt(new \DateTime());

        $this->saveJob($job);

        $this->sendCalculateRootJobStatusEvent($job);
    }

    /**
     * @param Job $job
     */
    public function successChildJob(Job $job)
    {
        if ($job->isRoot()) {
            throw new \LogicException(sprintf('Can\'t success root jobs. id: "%s"', $job->getId()));
        }

        $job = $this->jobStorage->findJobById($job->getId());

        if (Job::STATUS_RUNNING !== $job->getStatus()) {
            throw new \LogicException(sprintf(
                'Can success only running jobs. id: "%s", status: "%s"',
                $job->getId(),
                $job->getStatus()
            ));
        }

        $job->setStatus(Job::STATUS_SUCCESS);
        $job->setStoppedAt(new \DateTime());

        $this->saveJob($job);

        $this->sendCalculateRootJobStatusEvent($job);
    }

    /**
     * @param Job $job
     */
    public function failChildJob(Job $job)
    {
        if ($job->isRoot()) {
            throw new \LogicException(sprintf('Can\'t fail root jobs. id: "%s"', $job->getId()));
        }

        $job = $this->jobStorage->findJobById($job->getId());

        if (Job::STATUS_RUNNING !== $job->getStatus()) {
            throw new \LogicException(sprintf(
                'Can fail only running jobs. id: "%s", status: "%s"',
                $job->getId(),
                $job->getStatus()
            ));
        }

        $job->setStatus(Job::STATUS_FAILED);
        $job->setStoppedAt(new \DateTime());

        $this->saveJob($job);

        $this->sendCalculateRootJobStatusEvent($job);
    }

    /**
     * @param Job $job
     */
    public function cancelChildJob(Job $job)
    {
        if ($job->isRoot()) {
            throw new \LogicException(sprintf('Can\'t cancel root jobs. id: "%s"', $job->getId()));
        }

        $job = $this->jobStorage->findJobById($job->getId());

        if (!in_array($job->getStatus(), [Job::STATUS_NEW, Job::STATUS_RUNNING], true)) {
            throw new \LogicException(sprintf(
                'Can cancel only new or running jobs. id: "%s", status: "%s"',
                $job->getId(),
                $job->getStatus()
            ));
        }

        $job->setStatus(Job::STATUS_CANCELLED);
        $job->setStoppedAt($stoppedAt = new \DateTime());

        if (!$job->getStartedAt()) {
            $job->setStartedAt($stoppedAt);
        }

        $this->saveJob($job);

        $this->sendCalculateRootJobStatusEvent($job);
    }

    /**
     * @param Job  $job
     * @param bool $force
     */
    public function interruptRootJob(Job $job, $force = false)
    {
        if (!$job->isRoot()) {
            throw new \LogicException(sprintf('Can interrupt only root jobs. id: "%s"', $job->getId()));
        }

        if ($job->isInterrupted()) {
            return;
        }

        $this->jobStorage->saveJob($job, function (Job $job) use ($force) {
            if ($job->isInterrupted()) {
                return;
            }

            $job->setInterrupted(true);

            if ($force) {
                $job->setStoppedAt(new \DateTime());
            }
        });
    }

    /**
     * @see https://github.com/php-enqueue/enqueue-dev/pull/222#issuecomment-336102749 See for rationale
     *
     * @param Job $job
     */
    protected function saveJob(Job $job)
    {
        $this->jobStorage->saveJob($job);
    }

    /**
     * @see https://github.com/php-enqueue/enqueue-dev/pull/222#issuecomment-336102749 See for rationale
     *
     * @param Job $job
     */
    protected function sendCalculateRootJobStatusEvent(Job $job)
    {
        $this->producer->sendEvent(Topics::CALCULATE_ROOT_JOB_STATUS, [
            'jobId' => $job->getId(),
        ]);
    }
}
