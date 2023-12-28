<?php

namespace Enqueue\JobQueue\Tests\Functional\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Enqueue\JobQueue\Job as BaseJob;

class Job extends BaseJob
{
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
        parent::__construct();

        $this->childJobs = new ArrayCollection();
    }
}
