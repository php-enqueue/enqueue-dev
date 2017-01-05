<?php

namespace Enqueue\JobQueue\Tests\Functional\Job;

use Enqueue\JobQueue\DuplicateJobException;
use Enqueue\JobQueue\JobStorage;
use Enqueue\JobQueue\Tests\Functional\Entity\Job;
use Enqueue\JobQueue\Tests\Functional\WebTestCase;

/**
 * @group functional
 */
class JobStorageTest extends WebTestCase
{
    public function testShouldFindJobById()
    {
        $job = new Job();
        $job->setName('name');
        $job->setStatus(Job::STATUS_NEW);
        $job->setCreatedAt(new \DateTime());

        $this->getEntityManager()->persist($job);
        $this->getEntityManager()->flush();
        $this->getEntityManager()->clear();

        $this->assertNotEmpty($job->getId());

        $resultJob = $this->getJobStorage()->findJobById($job->getId());

        $this->assertEquals($job->getId(), $resultJob->getId());
        $this->assertEquals('name', $resultJob->getName());
        $this->assertEquals(Job::STATUS_NEW, $resultJob->getStatus());
    }

    public function testCouldCreateJobWithoutLock()
    {
        $rootJob = new Job();
        $rootJob->setOwnerId('owner-id');
        $rootJob->setName('name');
        $rootJob->setStatus(Job::STATUS_NEW);
        $rootJob->setCreatedAt(new \DateTime());

        $this->getJobStorage()->saveJob($rootJob);

        $job = new Job();
        $job->setName('name');
        $job->setStatus(Job::STATUS_NEW);
        $job->setCreatedAt(new \DateTime());
        $job->setRootJob($rootJob);

        $this->getJobStorage()->saveJob($job);
        $this->getEntityManager()->clear();

        $resultJob = $this->getJobStorage()->findJobById($job->getId());

        $this->assertNotEmpty($job->getId());
        $this->assertEquals($job->getId(), $resultJob->getId());
    }

    public function testCouldUpdateJobWithoutLock()
    {
        $rootJob = new Job();
        $rootJob->setOwnerId('owner-id');
        $rootJob->setName('name');
        $rootJob->setStatus(Job::STATUS_NEW);
        $rootJob->setCreatedAt(new \DateTime());

        $this->getJobStorage()->saveJob($rootJob);

        $job = new Job();
        $job->setName('name');
        $job->setStatus(Job::STATUS_NEW);
        $job->setCreatedAt(new \DateTime());
        $job->setRootJob($rootJob);

        $this->getJobStorage()->saveJob($job);

        $job->setStatus(Job::STATUS_FAILED);
        $this->getJobStorage()->saveJob($job);

        $this->getEntityManager()->clear();

        $resultJob = $this->getJobStorage()->findJobById($job->getId());

        $this->assertNotEmpty($job->getId());
        $this->assertEquals($job->getId(), $resultJob->getId());
        $this->assertEquals(Job::STATUS_FAILED, $resultJob->getStatus());
    }

    public function testCouldUpdateJobWithLock()
    {
        $job = new Job();
        $job->setOwnerId('owner-id');
        $job->setName('name');
        $job->setStatus(Job::STATUS_NEW);
        $job->setCreatedAt(new \DateTime());

        $this->getJobStorage()->saveJob($job);

        $this->getJobStorage()->saveJob($job, function (Job $job) {
            $job->setStatus(Job::STATUS_CANCELLED);
        });

        $this->getEntityManager()->clear();

        $resultJob = $this->getJobStorage()->findJobById($job->getId());

        $this->assertNotEmpty($job->getId());
        $this->assertEquals($job->getId(), $resultJob->getId());
        $this->assertEquals(Job::STATUS_CANCELLED, $resultJob->getStatus());
    }

    public function testShouldThrowIfDuplicateJob()
    {
        $job1 = new Job();
        $job1->setOwnerId('owner-id1');
        $job1->setName('name');
        $job1->setUnique(true);
        $job1->setStatus(Job::STATUS_NEW);
        $job1->setCreatedAt(new \DateTime());

        $this->getJobStorage()->saveJob($job1);

        $job2 = new Job();
        $job2->setOwnerId('owner-id2');
        $job2->setName('name');
        $job2->setUnique(true);
        $job2->setStatus(Job::STATUS_NEW);
        $job2->setCreatedAt(new \DateTime());

        $this->setExpectedException(DuplicateJobException::class);

        $this->getJobStorage()->saveJob($job2);
    }

    /**
     * @return \Doctrine\ORM\EntityManager
     */
    private function getEntityManager()
    {
        return $this->container->get('doctrine.orm.default_entity_manager');
    }

    /**
     * @return \Enqueue\JobQueue\JobStorage
     */
    private function getJobStorage()
    {
        return new JobStorage($this->container->get('doctrine'), Job::class, 'enqueue_job_queue_unique');
    }
}
