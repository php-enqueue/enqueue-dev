<?php

namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Extension\LimitConsumedMessagesExtension;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\SubscriptionConsumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LimitConsumedMessagesExtensionTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new LimitConsumedMessagesExtension(12345);
    }

    public function testOnPreConsumeShouldInterruptWhenLimitIsReached()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with('[LimitConsumedMessagesExtension] Message consumption is interrupted since'.
                ' the message limit reached. limit: "3"')
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
        $extension = new LimitConsumedMessagesExtension(3);

        $extension->onPreConsume($context);
        $this->assertFalse($context->isExecutionInterrupted());

        $postReceivedMessage = new PostMessageReceived(
            $this->createInteropContextMock(),
            $this->createMock(Consumer::class),
            $this->createMock(Message::class),
            'aResult',
            1,
            new NullLogger()
        );

        $extension->onPostMessageReceived($postReceivedMessage);
        $extension->onPostMessageReceived($postReceivedMessage);
        $extension->onPostMessageReceived($postReceivedMessage);

        $extension->onPreConsume($context);
        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnPreConsumeShouldInterruptExecutionIfLimitIsZero()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with('[LimitConsumedMessagesExtension] Message consumption is interrupted since'.
                ' the message limit reached. limit: "0"')
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
        $extension = new LimitConsumedMessagesExtension(0);

        // consume 1
        $extension->onPreConsume($context);
        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnPreConsumeShouldInterruptExecutionIfLimitIsLessThatZero()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with('[LimitConsumedMessagesExtension] Message consumption is interrupted since'.
                ' the message limit reached. limit: "-1"')
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
        $extension = new LimitConsumedMessagesExtension(-1);

        // consume 1
        $extension->onPreConsume($context);
        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldInterruptExecutionIfMessageLimitExceeded()
    {
        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('debug')
            ->with('[LimitConsumedMessagesExtension] Message consumption is interrupted since'.
                ' the message limit reached. limit: "2"')
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
        $extension = new LimitConsumedMessagesExtension(2);

        // consume 1
        $extension->onPostMessageReceived($postReceivedMessage);
        $this->assertFalse($postReceivedMessage->isExecutionInterrupted());

        // consume 2 and exit
        $extension->onPostMessageReceived($postReceivedMessage);
        $this->assertTrue($postReceivedMessage->isExecutionInterrupted());
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
