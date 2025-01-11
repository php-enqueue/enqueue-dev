<?php

namespace Enqueue\JobQueue\Tests;

use Enqueue\Client\ProducerInterface;
use Enqueue\Consumption\Result;
use Enqueue\JobQueue\CalculateRootJobStatusProcessor;
use Enqueue\JobQueue\CalculateRootJobStatusService;
use Enqueue\JobQueue\Commands;
use Enqueue\JobQueue\Doctrine\JobStorage;
use Enqueue\JobQueue\Job;
use Enqueue\JobQueue\Topics;
use Enqueue\Null\NullMessage;
use Interop\Queue\Context;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class CalculateRootJobStatusProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @doesNotPerformAssertions
     */
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new CalculateRootJobStatusProcessor(
            $this->createJobStorageMock(),
            $this->createCalculateRootJobStatusCaseMock(),
            $this->createProducerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldReturnSubscribedTopicNames()
    {
        $this->assertEquals(
            Commands::CALCULATE_ROOT_JOB_STATUS,
            CalculateRootJobStatusProcessor::getSubscribedCommand()
        );
    }

    public function testShouldLogErrorAndRejectMessageIfMessageIsInvalid()
    {
        $message = new NullMessage();
        $message->setBody('');

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Got invalid message. body: ""')
        ;

        $processor = new CalculateRootJobStatusProcessor(
            $this->createJobStorageMock(),
            $this->createCalculateRootJobStatusCaseMock(),
            $this->createProducerMock(),
            $logger
        );
        $result = $processor->process($message, $this->createContextMock());

        $this->assertEquals(Result::REJECT, $result);
    }

    public function testShouldRejectMessageAndLogErrorIfJobWasNotFound()
    {
        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with('12345')
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('critical')
            ->with('Job was not found. id: "12345"')
        ;

        $case = $this->createCalculateRootJobStatusCaseMock();
        $case
            ->expects($this->never())
            ->method('calculate')
        ;

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->never())
            ->method('sendEvent')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode(['jobId' => 12345]));

        $processor = new CalculateRootJobStatusProcessor($storage, $case, $producer, $logger);
        $result = $processor->process($message, $this->createContextMock());

        $this->assertEquals(Result::REJECT, $result);
    }

    public function testShouldCallCalculateJobRootStatusAndACKMessage()
    {
        $job = new Job();

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with('12345')
            ->willReturn($job)
        ;

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->never())
            ->method('critical')
        ;

        $case = $this->createCalculateRootJobStatusCaseMock();
        $case
            ->expects($this->once())
            ->method('calculate')
            ->with($this->identicalTo($job))
        ;

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->never())
            ->method('sendEvent')
        ;

        $message = new NullMessage();
        $message->setBody(json_encode(['jobId' => 12345]));

        $processor = new CalculateRootJobStatusProcessor($storage, $case, $producer, $logger);
        $result = $processor->process($message, $this->createContextMock());

        $this->assertEquals(Result::ACK, $result);
    }

    public function testShouldSendRootJobStoppedMessageIfJobHasStopped()
    {
        $rootJob = new Job();
        $rootJob->setId(12345);
        $job = new Job();
        $job->setRootJob($rootJob);

        $storage = $this->createJobStorageMock();
        $storage
            ->expects($this->once())
            ->method('findJobById')
            ->with('12345')
            ->willReturn($job)
        ;

        $logger = $this->createLoggerMock();

        $case = $this->createCalculateRootJobStatusCaseMock();
        $case
            ->expects($this->once())
            ->method('calculate')
            ->with($this->identicalTo($job))
            ->willReturn(true)
        ;

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('sendEvent')
            ->with(Topics::ROOT_JOB_STOPPED, ['jobId' => 12345])
        ;

        $message = new NullMessage();
        $message->setBody(json_encode(['jobId' => 12345]));

        $processor = new CalculateRootJobStatusProcessor($storage, $case, $producer, $logger);
        $result = $processor->process($message, $this->createContextMock());

        $this->assertEquals(Result::ACK, $result);
    }

    /**
     * @return MockObject|ProducerInterface
     */
    private function createProducerMock()
    {
        return $this->createMock(ProducerInterface::class);
    }

    /**
     * @return MockObject|Context
     */
    private function createContextMock()
    {
        return $this->createMock(Context::class);
    }

    /**
     * @return MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return MockObject|CalculateRootJobStatusService
     */
    private function createCalculateRootJobStatusCaseMock()
    {
        return $this->createMock(CalculateRootJobStatusService::class);
    }

    /**
     * @return MockObject|JobStorage
     */
    private function createJobStorageMock()
    {
        return $this->createMock(JobStorage::class);
    }
}
