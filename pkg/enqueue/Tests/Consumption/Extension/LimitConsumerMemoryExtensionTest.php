<?php

namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Extension\LimitConsumerMemoryExtension;
use Interop\Queue\Consumer;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Interop\Queue\SubscriptionConsumer;
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
        $this->setExpectedException(\InvalidArgumentException::class, 'Expected memory limit is int but got: "double"');

        new LimitConsumerMemoryExtension(0.0);
    }

    public function testOnIdleShouldInterruptExecutionIfMemoryLimitReached()
    {
        $context = $this->createContext();
        $context->getLogger()
            ->expects($this->once())
            ->method('debug')
            ->with($this->stringContains('[LimitConsumerMemoryExtension] Interrupt execution as memory limit reached.'))
        ;

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(1);
        $extension->onIdle($context);

        $this->assertTrue($context->isExecutionInterrupted());
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

    public function testOnIdleShouldNotInterruptExecutionIfMemoryLimitIsNotReached()
    {
        $context = $this->createContext();

        // guard
        $this->assertFalse($context->isExecutionInterrupted());

        // test
        $extension = new LimitConsumerMemoryExtension(PHP_INT_MAX);
        $extension->onIdle($context);

        $this->assertFalse($context->isExecutionInterrupted());
    }

    public function testOnPostMessageReceivedShouldNotInterruptExecutionIfMemoryLimitIsNotReached()
    {
        $postReceivedMessage = new PostMessageReceived(
            $this->createInteropContextMock(),
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
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createInteropContextMock(): \Interop\Queue\Context
    {
        return $this->createMock(\Interop\Queue\Context::class);
    }

    /**
     * @return Context
     */
    protected function createContext(): Context
    {
        $context = new Context($this->createMock(InteropContext::class));
        $context->setLogger($this->createMock(LoggerInterface::class));
        $context->setConsumer($this->createMock(Consumer::class));
        $context->setProcessor($this->createMock(Processor::class));

        return $context;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createSubscriptionConsumerMock(): SubscriptionConsumer
    {
        return $this->createMock(SubscriptionConsumer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createLoggerMock(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }
}
