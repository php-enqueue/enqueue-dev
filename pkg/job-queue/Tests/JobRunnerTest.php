<?php

namespace Enqueue\JobQueue\Tests;

use Enqueue\JobQueue\Job;
use Enqueue\JobQueue\JobProcessor;
use Enqueue\JobQueue\JobRunner;
use Enqueue\JobQueue\OrphanJobException;

class JobRunnerTest extends \PHPUnit\Framework\TestCase
{
    public function testRunUniqueShouldCreateRootAndChildJobAndCallCallback()
    {
        $root = new Job();
        $child = new Job();

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->with('owner-id', 'job-name', true)
            ->will($this->returnValue($root))
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->with('job-name')
            ->will($this->returnValue($child))
        ;

        $expChild = null;
        $expRunner = null;

        $jobRunner = new JobRunner($jobProcessor);
        $result = $jobRunner->runUnique(
            'owner-id',
            'job-name',
            function (JobRunner $runner, Job $child) use (&$expRunner, &$expChild) {
                $expRunner = $runner;
                $expChild = $child;

                return 'return-value';
            }
        );

        $this->assertInstanceOf(JobRunner::class, $expRunner);
        $this->assertSame($expChild, $child);
        $this->assertEquals('return-value', $result);
    }

    public function testRunUniqueShouldStartChildJobIfNotStarted()
    {
        $root = new Job();
        $child = new Job();

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root))
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->will($this->returnValue($child))
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('startChildJob')
            ->with($child)
        ;

        $jobRunner = new JobRunner($jobProcessor);
        $jobRunner->runUnique('owner-id', 'job-name', function () {
        });
    }

    public function testRunUniqueShouldNotStartChildJobIfAlreadyStarted()
    {
        $root = new Job();
        $child = new Job();
        $child->setStartedAt(new \DateTime());

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root))
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->will($this->returnValue($child))
        ;
        $jobProcessor
            ->expects($this->never())
            ->method('startChildJob')
        ;

        $jobRunner = new JobRunner($jobProcessor);
        $jobRunner->runUnique('owner-id', 'job-name', function () {
        });
    }

    public function testRunUniqueShouldSuccessJobIfCallbackReturnValueIsTrue()
    {
        $root = new Job();
        $child = new Job();

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root))
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->will($this->returnValue($child))
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('successChildJob')
        ;
        $jobProcessor
            ->expects($this->never())
            ->method('failChildJob')
        ;

        $jobRunner = new JobRunner($jobProcessor);
        $jobRunner->runUnique('owner-id', 'job-name', function () {
            return true;
        });
    }

    public function testRunUniqueShouldFailJobIfCallbackReturnValueIsFalse()
    {
        $root = new Job();
        $child = new Job();

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root))
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->will($this->returnValue($child))
        ;
        $jobProcessor
            ->expects($this->never())
            ->method('successChildJob')
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('failChildJob')
        ;

        $jobRunner = new JobRunner($jobProcessor);
        $jobRunner->runUnique('owner-id', 'job-name', function () {
            return false;
        });
    }

    public function testRunUniqueShouldFailJobIfCallbackThrowsException()
    {
        $root = new Job();
        $child = new Job();

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root))
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->will($this->returnValue($child))
        ;
        $jobProcessor
            ->expects($this->never())
            ->method('successChildJob')
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('failChildJob')
        ;

        $jobRunner = new JobRunner($jobProcessor);
        $this->expectException(\Exception::class);
        $jobRunner->runUnique('owner-id', 'job-name', function () {
            throw new \Exception();
        });
    }

    public function testRunUniqueShouldThrowOrphanJobExceptionIfChildCleanupFails()
    {
        $root = new Job();
        $child = new Job();

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root))
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->will($this->returnValue($child))
        ;
        $jobProcessor
            ->expects($this->never())
            ->method('successChildJob')
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('failChildJob')
            ->willThrowException(new \Exception())
        ;

        $jobRunner = new JobRunner($jobProcessor);
        $this->expectException(OrphanJobException::class);
        $jobRunner->runUnique('owner-id', 'job-name', function () {
            throw new \Exception();
        });
    }

    public function testRunUniqueShouldNotSuccessJobIfJobIsAlreadyStopped()
    {
        $root = new Job();
        $child = new Job();
        $child->setStoppedAt(new \DateTime());

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateRootJob')
            ->will($this->returnValue($root))
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->will($this->returnValue($child))
        ;
        $jobProcessor
            ->expects($this->never())
            ->method('successChildJob')
        ;
        $jobProcessor
            ->expects($this->never())
            ->method('failChildJob')
        ;

        $jobRunner = new JobRunner($jobProcessor);
        $jobRunner->runUnique('owner-id', 'job-name', function () {
            return true;
        });
    }

    public function testCreateDelayedShouldCreateChildJobAndCallCallback()
    {
        $root = new Job();
        $child = new Job();

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects($this->once())
            ->method('findOrCreateChildJob')
            ->with('job-name', $this->identicalTo($root))
            ->will($this->returnValue($child))
        ;

        $expRunner = null;
        $expJob = null;

        $jobRunner = new JobRunner($jobProcessor, $root);
        $jobRunner->createDelayed('job-name', function (JobRunner $runner, Job $job) use (&$expRunner, &$expJob) {
            $expRunner = $runner;
            $expJob = $job;

            return true;
        });

        $this->assertInstanceOf(JobRunner::class, $expRunner);
        $this->assertSame($expJob, $child);
    }

    public function testRunDelayedShouldThrowExceptionIfJobWasNotFoundById()
    {
        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with('job-id')
            ->will($this->returnValue(null))
        ;

        $jobRunner = new JobRunner($jobProcessor);

        $this->setExpectedException(\LogicException::class, 'Job was not found. id: "job-id"');

        $jobRunner->runDelayed('job-id', function () {
        });
    }

    public function testRunDelayedShouldFindJobAndCallCallback()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with('job-id')
            ->will($this->returnValue($child))
        ;

        $expRunner = null;
        $expJob = null;

        $jobRunner = new JobRunner($jobProcessor);
        $jobRunner->runDelayed('job-id', function (JobRunner $runner, Job $job) use (&$expRunner, &$expJob) {
            $expRunner = $runner;
            $expJob = $job;

            return true;
        });

        $this->assertInstanceOf(JobRunner::class, $expRunner);
        $this->assertSame($expJob, $child);
    }

    public function testRunDelayedShouldCancelJobIfRootJobIsInterrupted()
    {
        $root = new Job();
        $root->setInterrupted(true);
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with('job-id')
            ->will($this->returnValue($child))
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('cancelChildJob')
            ->with($this->identicalTo($child))
        ;

        $jobRunner = new JobRunner($jobProcessor);
        $jobRunner->runDelayed('job-id', function (JobRunner $runner, Job $job) {
            return true;
        });
    }

    public function testRunDelayedShouldSuccessJobIfCallbackReturnValueIsTrue()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with('job-id')
            ->will($this->returnValue($child))
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('successChildJob')
            ->with($this->identicalTo($child))
        ;
        $jobProcessor
            ->expects($this->never())
            ->method('failChildJob')
        ;

        $jobRunner = new JobRunner($jobProcessor);
        $jobRunner->runDelayed('job-id', function (JobRunner $runner, Job $job) {
            return true;
        });
    }

    public function testRunDelayedShouldFailJobIfCallbackReturnValueIsFalse()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with('job-id')
            ->will($this->returnValue($child))
        ;
        $jobProcessor
            ->expects($this->never())
            ->method('successChildJob')
        ;
        $jobProcessor
            ->expects($this->once())
            ->method('failChildJob')
            ->with($this->identicalTo($child))
        ;

        $jobRunner = new JobRunner($jobProcessor);
        $jobRunner->runDelayed('job-id', function (JobRunner $runner, Job $job) {
            return false;
        });
    }

    public function testRunDelayedShouldNotSuccessJobIfAlreadyStopped()
    {
        $root = new Job();
        $child = new Job();
        $child->setRootJob($root);
        $child->setStoppedAt(new \DateTime());

        $jobProcessor = $this->createJobProcessorMock();
        $jobProcessor
            ->expects($this->once())
            ->method('findJobById')
            ->with('job-id')
            ->will($this->returnValue($child))
        ;
        $jobProcessor
            ->expects($this->never())
            ->method('successChildJob')
        ;
        $jobProcessor
            ->expects($this->never())
            ->method('failChildJob')
        ;

        $jobRunner = new JobRunner($jobProcessor);
        $jobRunner->runDelayed('job-id', function (JobRunner $runner, Job $job) {
            return true;
        });
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobProcessor
     */
    private function createJobProcessorMock()
    {
        return $this->createMock(JobProcessor::class);
    }
}
