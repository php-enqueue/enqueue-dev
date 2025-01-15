<?php

namespace Enqueue\JobQueue\Tests\Doctrine;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Enqueue\JobQueue\Doctrine\JobStorage;
use Enqueue\JobQueue\DuplicateJobException;
use Enqueue\JobQueue\Job;
use PHPUnit\Framework\MockObject\MockObject;

class JobStorageTest extends \PHPUnit\Framework\TestCase
{
    public function testShouldCreateJobObject()
    {
        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->willReturn(Job::class)
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->willReturn($repository)
        ;
        $em
            ->expects($this->any())
            ->method('isOpen')
            ->willReturn(true)
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->willReturn($em)
        ;
        $doctrine
            ->expects($this->never())
            ->method('resetManager')
        ;

        $storage = new JobStorage($doctrine, 'entity-class', 'unique_table');
        $job = $storage->createJob();

        $this->assertInstanceOf(Job::class, $job);
    }

    public function testShouldResetManagerAndCreateJobObject()
    {
        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->willReturn(Job::class)
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->willReturn($repository)
        ;
        $em
            ->expects($this->any())
            ->method('isOpen')
            ->willReturn(false)
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->willReturn($em)
        ;
        $doctrine
            ->expects($this->any())
            ->method('resetManager')
            ->willReturn($em)
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
            ->willReturn('expected\class\name')
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->willReturn($repository)
        ;
        $em
            ->expects($this->any())
            ->method('isOpen')
            ->willReturn(true)
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->willReturn($em)
        ;
        $doctrine
            ->expects($this->never())
            ->method('resetManager')
        ;

        $storage = new JobStorage($doctrine, 'entity-class', 'unique_table');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Got unexpected job instance: expected: "expected\class\name", actual" "Enqueue\JobQueue\Job"');

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
            ->willReturn(Job::class)
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->willReturn($repository)
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
        $em
            ->expects($this->any())
            ->method('isOpen')
            ->willReturn(true)
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->willReturn($em)
        ;
        $doctrine
            ->expects($this->never())
            ->method('resetManager')
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
            ->willReturn(Job::class)
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->willReturn($repository)
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
        $em
            ->expects($this->any())
            ->method('isOpen')
            ->willReturn(true)
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->willReturn($em)
        ;
        $doctrine
            ->expects($this->never())
            ->method('resetManager')
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
            ->willReturn(Job::class)
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(function ($callback) use ($connection) {
                $callback($connection);
            })
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
            ->willReturn($repository)
        ;
        $em
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection)
        ;
        $em
            ->expects($this->any())
            ->method('isOpen')
            ->willReturn(true)
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->willReturn($em)
        ;
        $doctrine
            ->expects($this->never())
            ->method('resetManager')
        ;

        $storage = new JobStorage($doctrine, 'entity-class', 'unique_table');

        $this->expectException(DuplicateJobException::class);
        $this->expectExceptionMessage('Duplicate job. ownerId:"owner-id", name:"job-name"');

        $storage->saveJob($job);
    }

    public function testShouldThrowIfTryToSaveNewEntityWithLock()
    {
        $job = new Job();

        $repository = $this->createRepositoryMock();
        $repository
            ->expects($this->once())
            ->method('getClassName')
            ->willReturn(Job::class)
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->willReturn($repository)
        ;
        $em
            ->expects($this->any())
            ->method('isOpen')
            ->willReturn(true)
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->willReturn($em)
        ;
        $doctrine
            ->expects($this->never())
            ->method('resetManager')
        ;

        $storage = new JobStorage($doctrine, 'entity-class', 'unique_table');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Is not possible to create new job with lock, only update is allowed');
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
            ->willReturn(Job::class)
        ;
        $repository
            ->expects($this->once())
            ->method('find')
            ->with(12345, LockMode::PESSIMISTIC_WRITE)
            ->willReturn($lockedJob)
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->willReturn($repository)
        ;
        $em
            ->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(function ($callback) use ($em) {
                $callback($em);
            })
        ;
        $em
            ->expects($this->any())
            ->method('isOpen')
            ->willReturn(true)
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->willReturn($em)
        ;
        $doctrine
            ->expects($this->never())
            ->method('resetManager')
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
            ->willReturnCallback(function ($callback) use ($connection) {
                $callback($connection);
            })
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
            ->willReturn(Job::class)
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->willReturn($repository)
        ;
        $em
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection)
        ;
        $em
            ->expects($this->once())
            ->method('persist')
        ;
        $em
            ->expects($this->once())
            ->method('flush')
        ;
        $em
            ->expects($this->any())
            ->method('isOpen')
            ->willReturn(true)
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->willReturn($em)
        ;
        $doctrine
            ->expects($this->never())
            ->method('resetManager')
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
            ->willReturn(Job::class)
        ;
        $repository
            ->expects($this->once())
            ->method('find')
            ->willReturn($job)
        ;

        $em = $this->createEntityManagerMock();
        $em
            ->expects($this->once())
            ->method('getRepository')
            ->with('entity-class')
            ->willReturn($repository)
        ;
        $em
            ->expects($this->once())
            ->method('transactional')
            ->willReturnCallback(function ($callback) use ($em) {
                $callback($em);
            })
        ;
        $em
            ->expects($this->exactly(2))
            ->method('getConnection')
            ->willReturn($connection)
        ;
        $em
            ->expects($this->any())
            ->method('isOpen')
            ->willReturn(true)
        ;

        $doctrine = $this->createDoctrineMock();
        $doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with('entity-class')
            ->willReturn($em)
        ;
        $doctrine
            ->expects($this->never())
            ->method('resetManager')
        ;

        $storage = new JobStorage($doctrine, 'entity-class', 'unique_table');
        $storage->saveJob($job, function () {
        });
    }

    /**
     * @return MockObject|ManagerRegistry
     */
    private function createDoctrineMock()
    {
        return $this->createMock(ManagerRegistry::class);
    }

    /**
     * @return MockObject|Connection
     */
    private function createConnectionMock()
    {
        return $this->createMock(Connection::class);
    }

    /**
     * @return MockObject|EntityManager
     */
    private function createEntityManagerMock()
    {
        return $this->createMock(EntityManager::class);
    }

    /**
     * @return MockObject|EntityRepository
     */
    private function createRepositoryMock()
    {
        return $this->createMock(EntityRepository::class);
    }

    /**
     * @return MockObject|UniqueConstraintViolationException
     */
    private function createUniqueConstraintViolationExceptionMock()
    {
        return $this->createMock(UniqueConstraintViolationException::class);
    }
}
