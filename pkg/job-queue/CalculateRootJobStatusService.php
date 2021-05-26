<?php

namespace Enqueue\JobQueue;

use Doctrine\Common\Collections\Collection;
use Enqueue\JobQueue\Doctrine\JobStorage;

class CalculateRootJobStatusService
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    public function __construct(JobStorage $jobStorage)
    {
        $this->jobStorage = $jobStorage;
    }

    /**
     * @return bool true if root job was stopped
     */
    public function calculate(Job $job)
    {
        $rootJob = $job->isRoot() ? $job : $job->getRootJob();
        $stopStatuses = [Job::STATUS_SUCCESS, Job::STATUS_FAILED, Job::STATUS_CANCELLED];

        if (in_array($rootJob->getStatus(), $stopStatuses, true)) {
            return;
        }

        $rootStopped = false;
        $this->jobStorage->saveJob($rootJob, function (Job $rootJob) use ($stopStatuses, &$rootStopped) {
            if (in_array($rootJob->getStatus(), $stopStatuses, true)) {
                return;
            }

            $childJobs = $rootJob->getChildJobs();
            if ($childJobs instanceof Collection) {
                $childJobs = $childJobs->toArray();
            }

            $status = $this->calculateRootJobStatus($childJobs);

            $rootJob->setStatus($status);

            if (in_array($status, $stopStatuses, true)) {
                $rootStopped = true;
                if (!$rootJob->getStoppedAt()) {
                    $rootJob->setStoppedAt(new \DateTime());
                }
            }
        });

        return $rootStopped;
    }

    /**
     * @param Job[] $jobs
     *
     * @return string
     */
    protected function calculateRootJobStatus(array $jobs)
    {
        $new = 0;
        $running = 0;
        $cancelled = 0;
        $failed = 0;
        $success = 0;

        foreach ($jobs as $job) {
            $this->jobStorage->refreshJobEntity($job);
            switch ($job->getStatus()) {
                case Job::STATUS_NEW:
                    $new++;
                    break;
                case Job::STATUS_RUNNING:
                    $running++;
                    break;
                case Job::STATUS_CANCELLED:
                    $cancelled++;
                    break;
                case Job::STATUS_FAILED:
                    $failed++;
                    break;
                case Job::STATUS_SUCCESS:
                    $success++;
                    break;
                default:
                    throw new \LogicException(sprintf('Got unsupported job status: id: "%s" status: "%s"', $job->getId(), $job->getStatus()));
            }
        }

        return $this->getRootJobStatus($new, $running, $cancelled, $failed, $success);
    }

    /**
     * @param int $new
     * @param int $running
     * @param int $cancelled
     * @param int $failed
     * @param int $success
     *
     * @return string
     */
    protected function getRootJobStatus($new, $running, $cancelled, $failed, $success)
    {
        $status = Job::STATUS_NEW;
        if (!$new && !$running) {
            if ($cancelled) {
                $status = Job::STATUS_CANCELLED;
            } elseif ($failed) {
                $status = Job::STATUS_FAILED;
            } else {
                $status = Job::STATUS_SUCCESS;
            }
        } elseif ($running || $cancelled || $failed || $success) {
            $status = Job::STATUS_RUNNING;
        }

        return $status;
    }
}
