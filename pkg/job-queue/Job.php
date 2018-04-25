<?php

namespace Enqueue\JobQueue;

class Job
{
    const STATUS_NEW = 'enqueue.job_queue.status.new';
    const STATUS_RUNNING = 'enqueue.job_queue.status.running';
    const STATUS_SUCCESS = 'enqueue.job_queue.status.success';
    const STATUS_FAILED = 'enqueue.job_queue.status.failed';
    const STATUS_CANCELLED = 'enqueue.job_queue.status.cancelled';

    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $ownerId;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $status;

    /**
     * @var bool
     */
    protected $interrupted;

    /**
     * @var bool;
     */
    protected $unique;

    /**
     * @var Job
     */
    protected $rootJob;

    /**
     * @var Job[]
     */
    protected $childJobs;

    /**
     * @var \DateTime
     */
    protected $createdAt;

    /**
     * @var \DateTime
     */
    protected $startedAt;

    /**
     * @var \DateTime
     */
    protected $stoppedAt;

    /**
     * @var array
     */
    protected $data;

    public function __construct()
    {
        $this->interrupted = false;
        $this->unique = false;
        $this->data = [];
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getOwnerId()
    {
        return $this->ownerId;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param string $ownerId
     */
    public function setOwnerId($ownerId)
    {
        $this->ownerId = $ownerId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function isInterrupted()
    {
        return $this->interrupted;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param bool $interrupted
     */
    public function setInterrupted($interrupted)
    {
        $this->interrupted = $interrupted;
    }

    /**
     * @return bool
     */
    public function isUnique()
    {
        return $this->unique;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param bool $unique
     */
    public function setUnique($unique)
    {
        $this->unique = $unique;
    }

    /**
     * @return Job
     */
    public function getRootJob()
    {
        return $this->rootJob;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param Job $rootJob
     */
    public function setRootJob(self $rootJob)
    {
        $this->rootJob = $rootJob;
    }

    /**
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param \DateTime $createdAt
     */
    public function setCreatedAt(\DateTime $createdAt)
    {
        $this->createdAt = $createdAt;
    }

    /**
     * @return \DateTime
     */
    public function getStartedAt()
    {
        return $this->startedAt;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param \DateTime $startedAt
     */
    public function setStartedAt(\DateTime $startedAt)
    {
        $this->startedAt = $startedAt;
    }

    /**
     * @return \DateTime
     */
    public function getStoppedAt()
    {
        return $this->stoppedAt;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param \DateTime $stoppedAt
     */
    public function setStoppedAt(\DateTime $stoppedAt)
    {
        $this->stoppedAt = $stoppedAt;
    }

    /**
     * @return bool
     */
    public function isRoot()
    {
        return null === $this->getRootJob();
    }

    /**
     * @return Job[]
     */
    public function getChildJobs()
    {
        return $this->childJobs;
    }

    /**
     * Only JobProcessor is responsible to call this method.
     * Do not call from the outside.
     *
     * @internal
     *
     * @param Job[] $childJobs
     */
    public function setChildJobs($childJobs)
    {
        $this->childJobs = $childJobs;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }
}
