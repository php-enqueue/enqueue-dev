<?php

namespace Enqueue\JobQueue\Tests;

use Enqueue\JobQueue\CalculateRootJobStatusService;
use Enqueue\JobQueue\Doctrine\JobStorage;
use Enqueue\JobQueue\Job;
use PHPUnit\Framework\MockObject\MockObject;

class CalculateRootJobStatusServiceTest extends \PHPUnit\Framework\TestCase
{
    public function stopStatusProvider()
    {
        return [
            [Job::STATUS_SUCCESS],
            [Job::STATUS_FAILED],
            [Job::STATUS_CANCELLED],
        ];
    }

    /**
     * @dataProvider stopStatusProvider
     *
     * @param mixed $status
     */
    public function testShouldDoNothingIfRootJobHasStopState($status)
    {
        $rootJob = new Job();
        $rootJob->setStatus($status);

        $notRootJob = new Job();
        $notRootJob->setRootJob($rootJob);

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->never())
            ->method('saveJob')
        ;

        $case = new CalculateRootJobStatusService($storage);
        $case->calculate($notRootJob);
    }

    public function testShouldCalculateRootJobStatus()
    {
        $rootJob = new Job();
        $rootJob->setId(123);

        $childJob = new Job();
        $childJob->setRootJob($rootJob);
        $childJob->setStatus(Job::STATUS_RUNNING);

        $rootJob->setChildJobs([$childJob]);

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            })
        ;

        $case = new CalculateRootJobStatusService($storage);
        $case->calculate($childJob);

        $this->assertEquals(Job::STATUS_RUNNING, $rootJob->getStatus());
        $this->assertNull($rootJob->getStoppedAt());
    }

    /**
     * @dataProvider stopStatusProvider
     *
     * @param mixed $stopStatus
     */
    public function testShouldCalculateRootJobStatusAndSetStoppedAtTimeIfGotStopStatus($stopStatus)
    {
        $rootJob = new Job();
        $rootJob->setId(123);

        $childJob = new Job();
        $childJob->setRootJob($rootJob);
        $childJob->setStatus($stopStatus);

        $rootJob->setChildJobs([$childJob]);

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            })
        ;

        $case = new CalculateRootJobStatusService($storage);
        $case->calculate($childJob);

        $this->assertEquals($stopStatus, $rootJob->getStatus());
        $this->assertEquals(
            (new \DateTime())->getTimestamp(),
            $rootJob->getStoppedAt()->getTimestamp()
        );
    }

    public function testShouldSetStoppedAtOnlyIfWasNotSet()
    {
        $rootJob = new Job();
        $rootJob->setId(123);
        $rootJob->setStoppedAt(new \DateTime('2012-12-12 12:12:12'));

        $childJob = new Job();
        $childJob->setRootJob($rootJob);
        $childJob->setStatus(Job::STATUS_SUCCESS);

        $rootJob->setChildJobs([$childJob]);

        $em = $this->createJobStorageMock();
        $em
            ->expects($this->once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            })
        ;

        $case = new CalculateRootJobStatusService($em);
        $case->calculate($childJob);

        $this->assertEquals(
            (new \DateTime('2012-12-12 12:12:12'))->getTimestamp(),
            $rootJob->getStoppedAt()->getTimestamp()
        );
    }

    public function testShouldThrowIfInvalidStatus()
    {
        $rootJob = new Job();

        $childJob = new Job();
        $childJob->setId(12345);
        $childJob->setRootJob($rootJob);
        $childJob->setStatus('invalid-status');

        $rootJob->setChildJobs([$childJob]);

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            })
        ;

        $case = new CalculateRootJobStatusService($storage);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Got unsupported job status: id: "12345" status: "invalid-status"');

        $case->calculate($childJob);
    }

    public function testShouldSetStatusNewIfAllChildAreNew()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_NEW);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_NEW);

        $rootJob->setChildJobs([$childJob1, $childJob2]);

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            })
        ;

        $case = new CalculateRootJobStatusService($storage);
        $case->calculate($rootJob);

        $this->assertEquals(Job::STATUS_NEW, $rootJob->getStatus());
    }

    public function testShouldSetStatusRunningIfAnyOneIsRunning()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_NEW);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_RUNNING);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_SUCCESS);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            })
        ;

        $case = new CalculateRootJobStatusService($storage);
        $case->calculate($rootJob);

        $this->assertEquals(Job::STATUS_RUNNING, $rootJob->getStatus());
    }

    public function testShouldSetStatusRunningIfThereIsNoRunningButNewAndAnyOfStopStatus()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_NEW);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_SUCCESS);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_CANCELLED);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            })
        ;

        $case = new CalculateRootJobStatusService($storage);
        $case->calculate($rootJob);

        $this->assertEquals(Job::STATUS_RUNNING, $rootJob->getStatus());
    }

    public function testShouldSetStatusCancelledIfAllIsStopButOneIsCancelled()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_SUCCESS);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_FAILED);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_CANCELLED);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            })
        ;

        $case = new CalculateRootJobStatusService($storage);
        $case->calculate($rootJob);

        $this->assertEquals(Job::STATUS_CANCELLED, $rootJob->getStatus());
    }

    public function testShouldSetStatusFailedIfThereIsAnyOneIsFailedButIsNotCancelled()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_SUCCESS);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_FAILED);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_SUCCESS);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            })
        ;

        $case = new CalculateRootJobStatusService($storage);
        $case->calculate($rootJob);

        $this->assertEquals(Job::STATUS_FAILED, $rootJob->getStatus());
    }

    public function testShouldSetStatusSuccessIfAllAreSuccess()
    {
        $rootJob = new Job();

        $childJob1 = new Job();
        $childJob1->setRootJob($rootJob);
        $childJob1->setStatus(Job::STATUS_SUCCESS);

        $childJob2 = new Job();
        $childJob2->setRootJob($rootJob);
        $childJob2->setStatus(Job::STATUS_SUCCESS);

        $childJob3 = new Job();
        $childJob3->setRootJob($rootJob);
        $childJob3->setStatus(Job::STATUS_SUCCESS);

        $rootJob->setChildJobs([$childJob1, $childJob2, $childJob3]);

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('saveJob')
            ->willReturnCallback(function (Job $job, $callback) {
                $callback($job);
            })
        ;

        $case = new CalculateRootJobStatusService($storage);
        $case->calculate($rootJob);

        $this->assertEquals(Job::STATUS_SUCCESS, $rootJob->getStatus());
    }

    /**
     * @return MockObject|\Enqueue\JobQueue\Doctrine\JobStorage
     */
    private function createJobStorageMock()
    {
        return $this->createMock(JobStorage::class);
    }
}
