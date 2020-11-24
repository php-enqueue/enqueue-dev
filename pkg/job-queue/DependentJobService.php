<?php

namespace Enqueue\JobQueue;

use Enqueue\JobQueue\Doctrine\JobStorage;

class DependentJobService
{
    /**
     * @var JobStorage
     */
    private $jobStorage;

    /**
     * @param JobStorage|null $jobStorage
     */
    public function __construct(JobStorage $jobStorage)
    {
        $this->jobStorage = $jobStorage;
    }

    /**
     * @return DependentJobContext
     */
    public function createDependentJobContext(Job $job)
    {
        return new DependentJobContext($job);
    }

    public function saveDependentJob(DependentJobContext $context)
    {
        if (!$context->getJob()->isRoot()) {
            throw new \LogicException(sprintf('Only root jobs allowed but got child. jobId: "%s"', $context->getJob()->getId()));
        }

        $this->jobStorage->saveJob($context->getJob(), function (Job $job) use ($context) {
            $data = $job->getData();
            $data['dependentJobs'] = $context->getDependentJobs();

            $job->setData($data);
        });
    }
}
