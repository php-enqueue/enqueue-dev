<?php

namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Extension\LimitConsumerMemoryExtension;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\SubscriptionConsumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LimitConsumerMemoryExtensionTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new LimitConsumerMemoryExtension(12345);
    }

    public function testShouldThrowExceptionIfMemoryLimitIsNotInt()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Expected memory limit is int but got: "double"');

        new LimitConsumerMemoryExtension(0.0);
    }

    public function testOnPostConsumeShouldInterruptExecutionIfMemoryLimitReached()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('[LimitConsumerMemoryExtension] Interrupt execution as memory limit reached.'))
        ;

        $postConsume = new PostConsume(
            $this->createInteropContextMock(),
            $this->createSubscriptionConsumerMock(),
            1,
            1,
            1,
            $logger
        );

        // guard
        $this->assertFalse($postConsume->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(1);
        $extension->onPostConsume($postConsume);

        $this->assertTrue($postConsume->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldInterruptExecutionIfMemoryLimitReached()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('[LimitConsumerMemoryExtension] Interrupt execution as memory limit reached.'))
        ;

        $postReceivedMessage = new PostMessageReceived(
            $this->createInteropContextMock(),
            $this->createMock(Consumer::class),
            $this->createMock(Message::class),
            'aResult',
            1,
            $logger
        );

        // guard
        $this->assertFalse($postReceivedMessage->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(1);
        $extension->onPostMessageReceived($postReceivedMessage);

        $this->assertTrue($postReceivedMessage->isExecutionInterrupted());
    }

    public function testOnPreConsumeShouldInterruptExecutionIfMemoryLimitReached()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('[LimitConsumerMemoryExtension] Interrupt execution as memory limit reached.'))
        ;

        $context = new PreConsume(
            $this->createInteropContextMock(),
            $this->createSubscriptionConsumerMock(),
            $logger,
            1,
            2,
            3
        );

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(1);
        $extension->onPreConsume($context);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnPreConsumeShouldNotInterruptExecutionIfMemoryLimitIsNotReached()
    {
        $context = new PreConsume(
            $this->createInteropContextMock(),
            $this->createSubscriptionConsumerMock(),
            new NullLogger(),
            1,
            2,
            3
        );

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(PHP_INT_MAX);
        $extension->onPreConsume($context);

        $this->assertFalse($context->isExecutionInterrupted());
    }

    public function testOnPostConsumeShouldNotInterruptExecutionIfMemoryLimitIsNotReached()
    {
        $postConsume = new PostConsume(
            $this->createInteropContextMock(),
            $this->createSubscriptionConsumerMock(),
            1,
            1,
            1,
            new NullLogger()
        );

        // guard
        $this->assertFalse($postConsume->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(PHP_INT_MAX);
        $extension->onPostConsume($postConsume);

        $this->assertFalse($postConsume->isExecutionInterrupted());
    }

    public function testOnPostMessageReceivedShouldNotInterruptExecutionIfMemoryLimitIsNotReached()
    {
        $postReceivedMessage = new PostMessageReceived(
            $this->createInteropContextMock(),
            $this->createMock(Consumer::class),
            $this->createMock(Message::class),
            'aResult',
            1,
            new NullLogger()
        );

        // guard
        $this->assertFalse($postReceivedMessage->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(PHP_INT_MAX);
        $extension->onPostMessageReceived($postReceivedMessage);

        $this->assertFalse($postReceivedMessage->isExecutionInterrupted());
    }

    /**
     * @return MockObject
     */
    protected function createInteropContextMock(): Context
    {
        return $this->createMock(Context::class);
    }

    /**
     * @return MockObject
     */
    private function createSubscriptionConsumerMock(): SubscriptionConsumer
    {
        return $this->createMock(SubscriptionConsumer::class);
    }

    /**
     * @return MockObject
     */
    private function createLoggerMock(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }
}
