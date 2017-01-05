<?php

namespace Enqueue\JobQueue\Tests;

use Enqueue\Client\MessageProducerInterface;
use Enqueue\Consumption\Result;
use Enqueue\JobQueue\CalculateRootJobStatusProcessor;
use Enqueue\JobQueue\CalculateRootJobStatusService;
use Enqueue\JobQueue\Job;
use Enqueue\JobQueue\JobStorage;
use Enqueue\JobQueue\Topics;
use Enqueue\Psr\Context;
use Enqueue\Transport\Null\NullMessage;
use Psr\Log\LoggerInterface;

class CalculateRootJobStatusProcessorTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new CalculateRootJobStatusProcessor(
            $this->createJobStorageMock(),
            $this->createCalculateRootJobStatusCaseMock(),
            $this->createMessageProducerMock(),
            $this->createLoggerMock()
        );
    }

    public function testShouldReturnSubscribedTopicNames()
    {
        $this->assertEquals(
            [Topics::CALCULATE_ROOT_JOB_STATUS],
            CalculateRootJobStatusProcessor::getSubscribedTopics()
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
            $this->createMessageProducerMock(),
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

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->never())
            ->method('send')
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
            ->will($this->returnValue($job))
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

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->never())
            ->method('send')
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
            ->will($this->returnValue($job))
        ;

        $logger = $this->createLoggerMock();

        $case = $this->createCalculateRootJobStatusCaseMock();
        $case
            ->expects($this->once())
            ->method('calculate')
            ->with($this->identicalTo($job))
            ->will($this->returnValue(true))
        ;

        $producer = $this->createMessageProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with(Topics::ROOT_JOB_STOPPED, ['jobId' => 12345])
        ;

        $message = new NullMessage();
        $message->setBody(json_encode(['jobId' => 12345]));

        $processor = new CalculateRootJobStatusProcessor($storage, $case, $producer, $logger);
        $result = $processor->process($message, $this->createContextMock());

        $this->assertEquals(Result::ACK, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProducerInterface
     */
    private function createMessageProducerMock()
    {
        return $this->createMock(MessageProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    private function createContextMock()
    {
        return $this->createMock(Context::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|CalculateRootJobStatusService
     */
    private function createCalculateRootJobStatusCaseMock()
    {
        return $this->createMock(CalculateRootJobStatusService::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|JobStorage
     */
    private function createJobStorageMock()
    {
        return $this->createMock(JobStorage::class);
    }
}
