<?php

namespace Enqueue\JobQueue\Tests\Doctrine;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Enqueue\JobQueue\DuplicateJobException;
use Enqueue\JobQueue\Job;
use Enqueue\JobQueue\Doctrine\JobStorage;

class JobStorageTest extends \PHPUnit\Framework\TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new JobStorage($this->createDoctrineMock(), 'entity-class', 'unique_table');
    }

    public function testShouldCreateJobObject()
    {
        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->will($this->returnValue($repository))
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->will($this->returnValue($em))
        ;

        $storage = new JobStorage($doctrine, 'entity-class', 'unique_table');
        $job = $storage->createJob();

        $this->assertInstanceOf(Job::class, $job);
    }

    public function testShouldThrowIfGotUnexpectedJobInstance()
    {
        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue('expected\class\name'))
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->will($this->returnValue($repository))
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->will($this->returnValue($em))
        ;

        $storage = new JobStorage($doctrine, 'entity-class', 'unique_table');

        $this->setExpectedException(
            \LogicException::class,
            'Got unexpected job instance: expected: "expected\class\name", '.
            'actual" "Enqueue\JobQueue\Job"'
        );

        $storage->saveJob(new Job());
    }

    public function testShouldSaveJobWithoutLockIfThereIsNoCallbackAndChildJob()
    {
        $job = new Job();

        $child = new Job();
        $child->setRootJob($job);

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->will($this->returnValue($repository))
        ;
        $em
            ->expects($this->once())
            ->method('persist')
            ->with($this->identicalTo($child))
        ;
        $em
            ->expects($this->once())
            ->method('flush')
        ;
        $em
            ->expects($this->never())
            ->method('transactional')
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->will($this->returnValue($em))
        ;

        $storage = new JobStorage($doctrine, 'entity-class', 'unique_table');
        $storage->saveJob($child);
    }

    public function testShouldSaveJobWithLockIfWithCallback()
    {
        $job = new Job();
        $job->setId(1234);

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->will($this->returnValue($repository))
        ;
        $em
            ->expects($this->never())
            ->method('persist')
            ->with($this->identicalTo($job))
        ;
        $em
            ->expects($this->never())
            ->method('flush')
        ;
        $em
            ->expects($this->once())
            ->method('transactional')
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->will($this->returnValue($em))
        ;

        $storage = new JobStorage($doctrine, 'entity-class', 'unique_table');
        $storage->saveJob($job, function () {
        });
    }

    public function testShouldCatchUniqueConstraintViolationExceptionAndThrowDuplicateJobException()
    {
        $job = new Job();
        $job->setOwnerId('owner-id');
        $job->setName('job-name');
        $job->setUnique(true);

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($connection) {
                $callback($connection);
            }))
        ;
        $connection
            ->expects($this->once())
            ->method('insert')
            ->will($this->throwException($this->createUniqueConstraintViolationExceptionMock()))
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->will($this->returnValue($repository))
        ;
        $em
            ->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection))
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->will($this->returnValue($em))
        ;

        $storage = new JobStorage($doctrine, 'entity-class', 'unique_table');

        $this->setExpectedException(DuplicateJobException::class, 'Duplicate job. ownerId:"owner-id", name:"job-name"');

        $storage->saveJob($job);
    }

    public function testShouldThrowIfTryToSaveNewEntityWithLock()
    {
        $job = new Job();

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->will($this->returnValue($repository))
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->will($this->returnValue($em))
        ;

        $storage = new JobStorage($doctrine, 'entity-class', 'unique_table');

        $this->setExpectedException(
            \LogicException::class,
            'Is not possible to create new job with lock, only update is allowed'
        );

        $storage->saveJob($job, function () {
        });
    }

    public function testShouldLockEntityAndPassNewInstanceIntoCallback()
    {
        $job = new Job();
        $job->setId(12345);
        $lockedJob = new Job();

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(12345, LockMode::PESSIMISTIC_WRITE)
            ->will($this->returnValue($lockedJob))
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->will($this->returnValue($repository))
        ;
        $em
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($em) {
                $callback($em);
            }))
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->will($this->returnValue($em))
        ;

        $storage = new JobStorage($doctrine, 'entity-class', 'unique_table');
        $resultJob = null;
        $storage->saveJob($job, function (Job $job) use (&$resultJob) {
            $resultJob = $job;
        });

        $this->assertSame($lockedJob, $resultJob);
    }

    public function testShouldInsertIntoUniqueTableIfJobIsUniqueAndNew()
    {
        $job = new Job();
        $job->setOwnerId('owner-id');
        $job->setName('job-name');
        $job->setUnique(true);

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($connection) {
                $callback($connection);
            }))
        ;
        $connection
            ->expects($this->at(0))
            ->method('insert')
            ->with('unique_table', ['name' => 'owner-id'])
        ;
        $connection
            ->expects($this->at(1))
            ->method('insert')
            ->with('unique_table', ['name' => 'job-name'])
        ;

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->will($this->returnValue($repository))
        ;
        $em
            ->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection))
        ;
        $em
            ->expects($this->once())
            ->method('persist')
        ;
        $em
            ->expects($this->once())
            ->method('flush')
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->will($this->returnValue($em))
        ;

        $storage = new JobStorage($doctrine, 'entity-class', 'unique_table');
        $storage->saveJob($job);
    }

    public function testShouldDeleteRecordFromUniqueTableIfJobIsUniqueAndStoppedAtIsSet()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setOwnerId('owner-id');
        $job->setName('job-name');
        $job->setUnique(true);
        $job->setStoppedAt(new \DateTime());

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->at(0))
            ->method('delete')
            ->with('unique_table', ['name' => 'owner-id'])
        ;
        $connection
            ->expects($this->at(1))
            ->method('delete')
            ->with('unique_table', ['name' => 'job-name'])
        ;

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->will($this->returnValue(Job::class))
        ;
        $repository
            ->expects($this->once())
            ->method('find')
            ->will($this->returnValue($job))
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->will($this->returnValue($repository))
        ;
        $em
            ->expects($this->once())
            ->method('transactional')
            ->will($this->returnCallback(function ($callback) use ($em) {
                $callback($em);
            }))
        ;
        $em
            ->expects($this->exactly(2))
            ->method('getConnection')
            ->will($this->returnValue($connection))
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->will($this->returnValue($em))
        ;

        $storage = new JobStorage($doctrine, 'entity-class', 'unique_table');
        $storage->saveJob($job, function () {
        });
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    private function createDoctrineMock()
    {
        return $this->createMock(ManagerRegistry::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function createConnectionMock()
    {
        return $this->createMock(Connection::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    private function createEntityManagerMock()
    {
        return $this->createMock(EntityManager::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EntityRepository
     */
    private function createRepositoryMock()
    {
        return $this->createMock(EntityRepository::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|UniqueConstraintViolationException
     */
    private function createUniqueConstraintViolationExceptionMock()
    {
        return $this->createMock(UniqueConstraintViolationException::class);
    }
}
