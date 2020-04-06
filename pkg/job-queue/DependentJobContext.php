<?php

namespace Enqueue\JobQueue;

class DependentJobContext
{
    /**
     * @var Job
     */
    private $job;

    /**
     * @var array
     */
    private $dependentJobs;

    public function __construct(Job $job)
    {
        $this->job = $job;
        $this->dependentJobs = [];
    }

    /**
     * @return Job
     */
    public function getJob()
    {
        return $this->job;
    }

    /**
     * @param string       $topic
     * @param string|array $message
     * @param int          $priority
     */
    public function addDependentJob($topic, $message, $priority = null)
    {
        $this->dependentJobs[] = [
            'topic' => $topic,
            'message' => $message,
            'priority' => $priority,
        ];
    }

    /**
     * @return array
     */
    public function getDependentJobs()
    {
        return $this->dependentJobs;
    }
}
