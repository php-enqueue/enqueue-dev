<?php

namespace Enqueue\JobQueue\Tests\Functional\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Enqueue\JobQueue\Job as BaseJob;

#[ORM\Entity]
#[ORM\Table(name: 'enqueue_job_queue')]
class Job extends BaseJob
{
    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    #[ORM\Column(name: 'owner_id', type: 'string', nullable: true)]
    protected $ownerId;

    #[ORM\Column(name: 'name', type: 'string', nullable: false)]
    protected $name;

    #[ORM\Column(name: 'status', type: 'string', nullable: false)]
    protected $status;

    #[ORM\Column(name: 'interrupted', type: 'boolean')]
    protected $interrupted;

    #[ORM\Column(name: '`unique`', type: 'boolean')]
    protected $unique;

    #[ORM\ManyToOne(targetEntity: 'Job', inversedBy: 'childJobs')]
    #[ORM\JoinColumn(name: 'root_job_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $rootJob;

    #[ORM\OneToMany(mappedBy: 'rootJob', targetEntity: 'Job')]
    protected $childJobs;

    #[ORM\Column(name: 'created_at', type: 'datetime', nullable: false)]
    protected $createdAt;

    #[ORM\Column(name: 'started_at', type: 'datetime', nullable: true)]
    protected $startedAt;

    #[ORM\Column(name: 'stopped_at', type: 'datetime', nullable: true)]
    protected $stoppedAt;

    #[ORM\Column(name: 'data', type: 'json', nullable: true)]
    protected $data;

    public function __construct()
    {
        parent::__construct();

        $this->childJobs = new ArrayCollection();
    }
}
