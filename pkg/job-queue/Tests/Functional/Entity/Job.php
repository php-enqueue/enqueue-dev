<?php

namespace Enqueue\JobQueue\Tests\Functional\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Enqueue\JobQueue\Job as BaseJob;

/**
 * @ORM\Entity
 * @ORM\Table(name="enqueue_job_queue")
 */
class Job extends BaseJob
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var string
     *
     * @ORM\Column(name="owner_id", type="string", nullable=true)
     */
    protected $ownerId;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", nullable=false)
     */
    protected $status;

    /**
     * @var bool
     *
     * @ORM\Column(name="interrupted", type="boolean")
     */
    protected $interrupted;

    /**
     * @var bool;
     *
     * @ORM\Column(name="`unique`", type="boolean")
     */
    protected $unique;

    /**
     * @var Job
     *
     * @ORM\ManyToOne(targetEntity="Job", inversedBy="childJobs")
     * @ORM\JoinColumn(name="root_job_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $rootJob;

    /**
     * @var Job[]
     *
     * @ORM\OneToMany(targetEntity="Job", mappedBy="rootJob")
     */
    protected $childJobs;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    protected $createdAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="started_at", type="datetime", nullable=true)
     */
    protected $startedAt;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="stopped_at", type="datetime", nullable=true)
     */
    protected $stoppedAt;

    /**
     * @var array
     *
     * @ORM\Column(name="data", type="json_array", nullable=true)
     */
    protected $data;

    public function __construct()
    {
        parent::__construct();

        $this->childJobs = new ArrayCollection();
    }
}
