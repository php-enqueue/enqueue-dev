<?php
namespace Enqueue\JobQueue;

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
     * @param Job $job
     *
     * @return DependentJobContext
     */
    public function createDependentJobContext(Job $job)
    {
        return new DependentJobContext($job);
    }

    /**
     * @param DependentJobContext $context
     */
    public function saveDependentJob(DependentJobContext $context)
    {
        if (!$context->getJob()->isRoot()) {
            throw new \LogicException(sprintf(
                'Only root jobs allowed but got child. jobId: "%s"',
                $context->getJob()->getId()
            ));
        }

        $this->jobStorage->saveJob($context->getJob(), function (Job $job) use ($context) {
            $data = $job->getData();
            $data['dependentJobs'] = $context->getDependentJobs();

            $job->setData($data);
        });
    }
}
