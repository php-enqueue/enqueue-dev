<?php

namespace Enqueue\JobQueue\Tests;

use Enqueue\Client\ProducerInterface;
use Enqueue\JobQueue\Commands;
use Enqueue\JobQueue\Doctrine\JobStorage;
use Enqueue\JobQueue\DuplicateJobException;
use Enqueue\JobQueue\Job;
use Enqueue\JobQueue\JobProcessor;
use PHPUnit\Framework\TestCase;

class JobProcessorTest extends TestCase
{
    public function testCouldBeCreatedWithRequiredArguments()
    {
        new JobProcessor($this->createJobStorage(), $this->createProducerMock());
    }

    public function testCreateRootJobShouldThrowIfOwnerIdIsEmpty()
    {
        $processor = new JobProcessor($this->createJobStorage(), $this->createProducerMock());

        $this->setExpectedException(\LogicException::class, 'OwnerId must not be empty');

        $processor->findOrCreateRootJob(null, 'job-name', true);
    }

    public function testCreateRootJobShouldThrowIfNameIsEmpty()
    {
        $processor = new JobProcessor($this->createJobStorage(), $this->createProducerMock());

        $this->setExpectedException(\LogicException::class, 'Job name must not be empty');

        $processor->findOrCreateRootJob('owner-id', null, true);
    }

    public function testShouldCreateRootJobAndReturnIt()
    {
        $job = new Job();

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('createJob')
            ->will($this->returnValue($job))
        ;
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->identicalTo($job))
        ;

        $processor = new JobProcessor($storage, $this->createProducerMock());

        $result = $processor->findOrCreateRootJob('owner-id', 'job-name', true);

        $this->assertSame($job, $result);
        $this->assertEquals(Job::STATUS_NEW, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getCreatedAt(), '', 1);
        $this->assertEquals(new \DateTime(), $job->getStartedAt(), '', 1);
        $this->assertNull($job->getStoppedAt());
        $this->assertEquals('job-name', $job->getName());
        $this->assertEquals('owner-id', $job->getOwnerId());
    }

    public function testShouldCatchDuplicateJobAndTryToFindJobByOwnerId()
    {
        $job = new Job();

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('createJob')
            ->will($this->returnValue($job))
        ;
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->identicalTo($job))
            ->will($this->throwException(new DuplicateJobException()))
        ;
        $storage
            ->expects($this->once())
            ->method('findRootJobByOwnerIdAndJobName')
            ->with('owner-id', 'job-name')
            ->will($this->returnValue($job))
        ;

        $processor = new JobProcessor($storage, $this->createProducerMock());

        $result = $processor->findOrCreateRootJob('owner-id', 'job-name', true);

        $this->assertSame($job, $result);
    }

    public function testCreateChildJobShouldThrowIfNameIsEmpty()
    {
        $processor = new JobProcessor($this->createJobStorage(), $this->createProducerMock());

        $this->setExpectedException(\LogicException::class, 'Job name must not be empty');

        $processor->findOrCreateChildJob(null, new Job());
    }

    public function testCreateChildJobShouldFindAndReturnAlreadyCreatedJob()
    {
        $job = new Job();
        $job->setId(123);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->never())
            ->method('createJob')
        ;
        $storage
            ->expects($this->never())
            ->method('saveJob')
        ;
        $storage
            ->expects($this->once())
            ->method('findChildJobByName')
            ->with('job-name', $this->identicalTo($job))
            ->will($this->returnValue($job))
        ;
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(123)
            ->will($this->returnValue($job))
        ;

        $processor = new JobProcessor($storage, $this->createProducerMock());

        $result = $processor->findOrCreateChildJob('job-name', $job);

        $this->assertSame($job, $result);
    }

    public function testCreateChildJobShouldCreateAndSaveJobAndPublishRecalculateRootMessage()
    {
        $job = new Job();
        $job->setId(12345);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('createJob')
            ->will($this->returnValue($job))
        ;
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->identicalTo($job))
        ;
        $storage
            ->expects($this->once())
            ->method('findChildJobByName')
            ->with('job-name', $this->identicalTo($job))
            ->will($this->returnValue(null))
        ;
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('sendEvent')
            ->with(Commands::CALCULATE_ROOT_JOB_STATUS, ['jobId' => 12345])
        ;

        $processor = new JobProcessor($storage, $producer);

        $result = $processor->findOrCreateChildJob('job-name', $job);

        $this->assertSame($job, $result);
        $this->assertEquals(Job::STATUS_NEW, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getCreatedAt(), '', 1);
        $this->assertNull($job->getStartedAt());
        $this->assertNull($job->getStoppedAt());
        $this->assertEquals('job-name', $job->getName());
        $this->assertNull($job->getOwnerId());
    }

    public function testStartChildJobShouldThrowIfRootJob()
    {
        $processor = new JobProcessor($this->createJobStorage(), $this->createProducerMock());

        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->setExpectedException(\LogicException::class, 'Can\'t start root jobs. id: "12345"');

        $processor->startChildJob($rootJob);
    }

    public function testStartChildJobShouldThrowIfJobHasNotNewStatus()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_CANCELLED);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $processor = new JobProcessor($storage, $this->createProducerMock());

        $this->setExpectedException(
            \LogicException::class,
            'Can start only new jobs: id: "12345", status: "enqueue.job_queue.status.cancelled"'
        );

        $processor->startChildJob($job);
    }

    public function testStartJobShouldUpdateJobWithRunningStatusAndStartAtTime()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_NEW);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->isInstanceOf(Job::class))
        ;
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('sendEvent')
        ;

        $processor = new JobProcessor($storage, $producer);
        $processor->startChildJob($job);

        $this->assertEquals(Job::STATUS_RUNNING, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getStartedAt(), '', 1);
    }

    public function testSuccessChildJobShouldThrowIfRootJob()
    {
        $processor = new JobProcessor($this->createJobStorage(), $this->createProducerMock());

        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->setExpectedException(\LogicException::class, 'Can\'t success root jobs. id: "12345"');

        $processor->successChildJob($rootJob);
    }

    public function testSuccessChildJobShouldThrowIfJobHasNotRunningStatus()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_CANCELLED);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $processor = new JobProcessor($storage, $this->createProducerMock());

        $this->setExpectedException(
            \LogicException::class,
            'Can success only running jobs. id: "12345", status: "enqueue.job_queue.status.cancelled"'
        );

        $processor->successChildJob($job);
    }

    public function testSuccessJobShouldUpdateJobWithSuccessStatusAndStopAtTime()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_RUNNING);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->isInstanceOf(Job::class))
        ;
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('sendEvent')
        ;

        $processor = new JobProcessor($storage, $producer);
        $processor->successChildJob($job);

        $this->assertEquals(Job::STATUS_SUCCESS, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getStoppedAt(), '', 1);
    }

    public function testFailChildJobShouldThrowIfRootJob()
    {
        $processor = new JobProcessor($this->createJobStorage(), $this->createProducerMock());

        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->setExpectedException(\LogicException::class, 'Can\'t fail root jobs. id: "12345"');

        $processor->failChildJob($rootJob);
    }

    public function testFailChildJobShouldThrowIfJobHasNotRunningStatus()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_CANCELLED);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $processor = new JobProcessor($storage, $this->createProducerMock());

        $this->setExpectedException(
            \LogicException::class,
            'Can fail only running jobs. id: "12345", status: "enqueue.job_queue.status.cancelled"'
        );

        $processor->failChildJob($job);
    }

    public function testFailJobShouldUpdateJobWithFailStatusAndStopAtTime()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_RUNNING);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->isInstanceOf(Job::class))
        ;
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('sendEvent')
        ;

        $processor = new JobProcessor($storage, $producer);
        $processor->failChildJob($job);

        $this->assertEquals(Job::STATUS_FAILED, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getStoppedAt(), '', 1);
    }

    public function testCancelChildJobShouldThrowIfRootJob()
    {
        $processor = new JobProcessor($this->createJobStorage(), $this->createProducerMock());

        $rootJob = new Job();
        $rootJob->setId(12345);

        $this->setExpectedException(\LogicException::class, 'Can\'t cancel root jobs. id: "12345"');

        $processor->cancelChildJob($rootJob);
    }

    public function testCancelChildJobShouldThrowIfJobHasNotNewOrRunningStatus()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_CANCELLED);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $processor = new JobProcessor($storage, $this->createProducerMock());

        $this->setExpectedException(
            \LogicException::class,
            'Can cancel only new or running jobs. id: "12345", status: "enqueue.job_queue.status.cancelled"'
        );

        $processor->cancelChildJob($job);
    }

    public function testCancelJobShouldUpdateJobWithCancelStatusAndStoppedAtTimeAndStartedAtTime()
    {
        $job = new Job();
        $job->setId(12345);
        $job->setRootJob(new Job());
        $job->setStatus(Job::STATUS_NEW);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->with($this->isInstanceOf(Job::class))
        ;
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with(12345)
            ->will($this->returnValue($job))
        ;

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('sendEvent')
        ;

        $processor = new JobProcessor($storage, $producer);
        $processor->cancelChildJob($job);

        $this->assertEquals(Job::STATUS_CANCELLED, $job->getStatus());
        $this->assertEquals(new \DateTime(), $job->getStoppedAt(), '', 1);
        $this->assertEquals(new \DateTime(), $job->getStartedAt(), '', 1);
    }

    public function testInterruptRootJobShouldThrowIfNotRootJob()
    {
        $notRootJob = new Job();
        $notRootJob->setId(123);
        $notRootJob->setRootJob(new Job());

        $processor = new JobProcessor($this->createJobStorage(), $this->createProducerMock());

        $this->setExpectedException(\LogicException::class, 'Can interrupt only root jobs. id: "123"');

        $processor->interruptRootJob($notRootJob);
    }

    public function testInterruptRootJobShouldDoNothingIfAlreadyInterrupted()
    {
        $rootJob = new Job();
        $rootJob->setId(123);
        $rootJob->setInterrupted(true);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->never())
            ->method('saveJob')
        ;

        $processor = new JobProcessor($storage, $this->createProducerMock());
        $processor->interruptRootJob($rootJob);
    }

    public function testInterruptRootJobShouldUpdateJobAndSetInterruptedTrue()
    {
        $rootJob = new Job();
        $rootJob->setId(123);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->will($this->returnCallback(function (Job $job, $callback) {
                $callback($job);
            }))
        ;

        $processor = new JobProcessor($storage, $this->createProducerMock());
        $processor->interruptRootJob($rootJob);

        $this->assertTrue($rootJob->isInterrupted());
        $this->assertNull($rootJob->getStoppedAt());
    }

    public function testInterruptRootJobShouldUpdateJobAndSetInterruptedTrueAndStoppedTimeIfForceTrue()
    {
        $rootJob = new Job();
        $rootJob->setId(123);

        $storage = $this->createJobStorage();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->will($this->returnCallback(function (Job $job, $callback) {
                $callback($job);
            }))
        ;

        $processor = new JobProcessor($storage, $this->createProducerMock());
        $processor->interruptRootJob($rootJob, true);

        $this->assertTrue($rootJob->isInterrupted());
        $this->assertEquals(new \DateTime(), $rootJob->getStoppedAt(), '', 1);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createJobStorage(): JobStorage
    {
        return $this->createMock(JobStorage::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createProducerMock(): ProducerInterface
    {
        return $this->createMock(ProducerInterface::class);
    }
}
