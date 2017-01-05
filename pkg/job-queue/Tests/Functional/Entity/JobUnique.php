<?php

namespace Enqueue\JobQueue\Tests\Functional\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="enqueue_job_queue_unique")
 */
class JobUnique
{
    /**
     * @ORM\Id
     * @ORM\Column(name="name", type="string", nullable=false)
     */
    protected $name;
}
