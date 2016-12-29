<?php
namespace Enqueue\EnqueueBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="enqueue_job_unique")
 */
class JobUnique
{
    /**
     * @ORM\Id
     * @ORM\Column(name="name", type="string")
     */
    protected $name;
}
