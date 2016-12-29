<?php
namespace Enqueue\JobQueue\Test;

use Enqueue\JobQueue\Job;
use Enqueue\JobQueue\JobRunner as BaseJobRunner;

class JobRunner extends BaseJobRunner
{
    /**
     * @var array
     */
    private $runUniqueJobs = [];

    /**
     * @var array
     */
    private $createDelayedJobs = [];

    /**
     * @var array
     */
    private $runDelayedJobs = [];

    public function __construct()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function runUnique($ownerId, $jobName, \Closure $runCallback)
    {
        $this->runUniqueJobs[] = ['ownerId' => $ownerId, 'jobName' => $jobName, 'runCallback' => $runCallback];

        return call_user_func($runCallback, $this, new Job());
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function createDelayed($jobName, \Closure $startCallback)
    {
        $this->createDelayedJobs[] = ['jobName' => $jobName, 'runCallback' => $startCallback];

        return call_user_func($startCallback, $this, new Job());
    }

    /**
     * {@inheritdoc}
     *
     * @return mixed
     */
    public function runDelayed($jobId, \Closure $runCallback)
    {
        $this->runDelayedJobs[] = ['jobId' => $jobId, 'runCallback' => $runCallback];

        return call_user_func($runCallback, $this, new Job());
    }

    /**
     * @return array
     */
    public function getRunUniqueJobs()
    {
        return $this->runUniqueJobs;
    }

    /**
     * @return array
     */
    public function getCreateDelayedJobs()
    {
        return $this->createDelayedJobs;
    }

    /**
     * @return array
     */
    public function getRunDelayedJobs()
    {
        return $this->runDelayedJobs;
    }
}
