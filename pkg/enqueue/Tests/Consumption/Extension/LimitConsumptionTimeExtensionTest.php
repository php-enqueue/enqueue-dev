<?php

namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Extension\LimitConsumptionTimeExtension;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\SubscriptionConsumer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LimitConsumptionTimeExtensionTest extends TestCase
{
    public function testCouldBeConstructedWithRequiredArguments()
    {
        new LimitConsumptionTimeExtension(new \DateTime('+1 day'));
    }

    public function testOnPreConsumeShouldInterruptExecutionIfConsumptionTimeExceeded()
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
        $extension = new LimitConsumptionTimeExtension(new \DateTime('-2 second'));

        $extension->onPreConsume($context);

        $this->assertTrue($context->isExecutionInterrupted());
    }

    public function testOnPostConsumeShouldInterruptExecutionIfConsumptionTimeExceeded()
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
        $extension = new LimitConsumptionTimeExtension(new \DateTime('-2 second'));

        $extension->onPostConsume($postConsume);

        $this->assertTrue($postConsume->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldInterruptExecutionIfConsumptionTimeExceeded()
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
        $extension = new LimitConsumptionTimeExtension(new \DateTime('-2 second'));

        $extension->onPostMessageReceived($postReceivedMessage);

        $this->assertTrue($postReceivedMessage->isExecutionInterrupted());
    }

    public function testOnPreConsumeShouldNotInterruptExecutionIfConsumptionTimeIsNotExceeded()
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
        $extension = new LimitConsumptionTimeExtension(new \DateTime('+2 second'));

        $extension->onPreConsume($context);

        $this->assertFalse($context->isExecutionInterrupted());
    }

    public function testOnPostConsumeShouldNotInterruptExecutionIfConsumptionTimeIsNotExceeded()
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
        $extension = new LimitConsumptionTimeExtension(new \DateTime('+2 second'));

        $extension->onPostConsume($postConsume);

        $this->assertFalse($postConsume->isExecutionInterrupted());
    }

    public function testOnPostReceivedShouldNotInterruptExecutionIfConsumptionTimeIsNotExceeded()
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
        $extension = new LimitConsumptionTimeExtension(new \DateTime('+2 second'));

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
