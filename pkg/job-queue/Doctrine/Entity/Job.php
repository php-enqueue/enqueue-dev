<?php
namespace Enqueue\JobQueue\Doctrine\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Enqueue\JobQueue\Job as BaseJob;

class Job extends BaseJob
{
    public function __construct()
    {
        parent::__construct();

        $this->childJobs = new ArrayCollection();
    }
}
