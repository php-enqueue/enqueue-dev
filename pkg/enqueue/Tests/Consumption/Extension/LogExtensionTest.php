<?php

namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Consumption\Context\End;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\EndExtensionInterface;
use Enqueue\Consumption\Extension\LogExtension;
use Enqueue\Consumption\MessageReceivedExtensionInterface;
use Enqueue\Consumption\PostMessageReceivedExtensionInterface;
use Enqueue\Consumption\Result;
use Enqueue\Consumption\StartExtensionInterface;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Util\Stringify;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Processor;
use Interop\Queue\Queue;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

class LogExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementStartExtensionInterface()
    {
        $this->assertClassImplements(StartExtensionInterface::class, LogExtension::class);
    }

    public function testShouldImplementEndExtensionInterface()
    {
        $this->assertClassImplements(EndExtensionInterface::class, LogExtension::class);
    }

    public function testShouldImplementMessageReceivedExtensionInterface()
    {
        $this->assertClassImplements(MessageReceivedExtensionInterface::class, LogExtension::class);
    }

    public function testShouldImplementPostMessageReceivedExtensionInterface()
    {
        $this->assertClassImplements(PostMessageReceivedExtensionInterface::class, LogExtension::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new LogExtension();
    }

    public function testShouldLogStartOnStart()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with('Consumption has started')
        ;

        $context = new Start($this->createContextMock(), $logger, [], 1, 1);

        $extension = new LogExtension();
        $extension->onStart($context);
    }

    public function testShouldLogEndOnEnd()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with('Consumption has ended')
        ;

        $context = new End($this->createContextMock(), 1, 2, $logger);

        $extension = new LogExtension();
        $extension->onEnd($context);
    }

    public function testShouldLogMessageReceived()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with('Received from {queueName}	{body}', [
                'queueName' => 'aQueue',
                'redelivered' => false,
                'body' => Stringify::that('aBody'),
                'properties' => Stringify::that(['aProp' => 'aPropVal']),
                'headers' => Stringify::that(['aHeader' => 'aHeaderVal']),
            ])
        ;

        $consumerMock = $this->createConsumerStub(new NullQueue('aQueue'));
        $message = new NullMessage('aBody');
        $message->setProperty('aProp', 'aPropVal');
        $message->setHeader('aHeader', 'aHeaderVal');

        $context = new MessageReceived($this->createContextMock(), $consumerMock, $message, $this->createProcessorMock(), 1, $logger);

        $extension = new LogExtension();
        $extension->onMessageReceived($context);
    }

    public function testShouldLogMessageProcessedWithStringResult()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::INFO,
                'Processed from {queueName}	{body}	{result}',
                [
                    'queueName' => 'aQueue',
                    'body' => Stringify::that('aBody'),
                    'properties' => Stringify::that(['aProp' => 'aPropVal']),
                    'headers' => Stringify::that(['aHeader' => 'aHeaderVal']),
                    'result' => 'aResult',
                    'reason' => '',
                ]
            )
        ;

        $consumerMock = $this->createConsumerStub(new NullQueue('aQueue'));
        $message = new NullMessage('aBody');
        $message->setProperty('aProp', 'aPropVal');
        $message->setHeader('aHeader', 'aHeaderVal');

        $context = new PostMessageReceived($this->createContextMock(), $consumerMock, $message, 'aResult', 1, $logger);

        $extension = new LogExtension();
        $extension->onPostMessageReceived($context);
    }

    public function testShouldLogRejectedMessageAsError()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::ERROR,
                'Processed from {queueName}	{body}	{result}',
                [
                    'queueName' => 'aQueue',
                    'body' => Stringify::that('aBody'),
                    'properties' => Stringify::that(['aProp' => 'aPropVal']),
                    'headers' => Stringify::that(['aHeader' => 'aHeaderVal']),
                    'result' => 'reject',
                    'reason' => '',
                ]
            )
        ;

        $consumerMock = $this->createConsumerStub(new NullQueue('aQueue'));
        $message = new NullMessage('aBody');
        $message->setProperty('aProp', 'aPropVal');
        $message->setHeader('aHeader', 'aHeaderVal');

        $context = new PostMessageReceived($this->createContextMock(), $consumerMock, $message, Processor::REJECT, 1, $logger);

        $extension = new LogExtension();
        $extension->onPostMessageReceived($context);
    }

    public function testShouldLogMessageProcessedWithResultObject()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::INFO,
                'Processed from {queueName}	{body}	{result}',
                [
                    'queueName' => 'aQueue',
                    'body' => Stringify::that('aBody'),
                    'properties' => Stringify::that(['aProp' => 'aPropVal']),
                    'headers' => Stringify::that(['aHeader' => 'aHeaderVal']),
                    'result' => 'ack',
                    'reason' => '',
                ]
            )
        ;

        $consumerMock = $this->createConsumerStub(new NullQueue('aQueue'));
        $message = new NullMessage('aBody');
        $message->setProperty('aProp', 'aPropVal');
        $message->setHeader('aHeader', 'aHeaderVal');

        $context = new PostMessageReceived($this->createContextMock(), $consumerMock, $message, Result::ack(), 1, $logger);

        $extension = new LogExtension();
        $extension->onPostMessageReceived($context);
    }

    public function testShouldLogMessageProcessedWithReasonResultObject()
    {
        $logger = $this->createLogger();
        $logger
            ->expects($this->once())
            ->method('log')
            ->with(LogLevel::INFO,
                'Processed from {queueName}	{body}	{result} {reason}',
                [
                    'queueName' => 'aQueue',
                    'body' => Stringify::that('aBody'),
                    'properties' => Stringify::that(['aProp' => 'aPropVal']),
                    'headers' => Stringify::that(['aHeader' => 'aHeaderVal']),
                    'result' => 'ack',
                    'reason' => 'aReason',
                ]
            )
        ;

        $consumerMock = $this->createConsumerStub(new NullQueue('aQueue'));
        $message = new NullMessage('aBody');
        $message->setProperty('aProp', 'aPropVal');
        $message->setHeader('aHeader', 'aHeaderVal');

        $context = new PostMessageReceived($this->createContextMock(), $consumerMock, $message, Result::ack('aReason'), 1, $logger);

        $extension = new LogExtension();
        $extension->onPostMessageReceived($context);
    }

    /**
     * @return MockObject
     */
    private function createConsumerStub(Queue $queue): Consumer
    {
        $consumerMock = $this->createMock(Consumer::class);
        $consumerMock
            ->expects($this->any())
            ->method('getQueue')
            ->willReturn($queue)
        ;

        return $consumerMock;
    }

    /**
     * @return MockObject
     */
    private function createContextMock(): Context
    {
        return $this->createMock(Context::class);
    }

    /**
     * @return MockObject
     */
    private function createProcessorMock(): Processor
    {
        return $this->createMock(Processor::class);
    }

    /**
     * @return MockObject|LoggerInterface
     */
    private function createLogger()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
