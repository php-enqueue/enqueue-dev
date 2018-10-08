<?php

namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\MessageResult;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\PreSubscribe;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Consumer;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Interop\Queue\SubscriptionConsumer;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class ChainExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, ChainExtension::class);
    }

    public function testCouldBeConstructedWithExtensionsArray()
    {
        new ChainExtension([$this->createExtension(), $this->createExtension()]);
    }

    public function testShouldProxyOnStartToAllInternalExtensions()
    {
        $context = new Start($this->createInteropContextMock(), $this->createLoggerMock(), [], 0, 0, 0);

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onStart')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onStart')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onStart($context);
    }

    public function testShouldProxyOnPreSubscribeToAllInternalExtensions()
    {
        $context = new PreSubscribe(
            $this->createInteropContextMock(),
            $this->createInteropProcessorMock(),
            $this->createInteropConsumerMock(),
            $this->createLoggerMock()
        );

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onPreSubscribe')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onPreSubscribe')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onPreSubscribe($context);
    }

    public function testShouldProxyOnPreConsumeToAllInternalExtensions()
    {
        $context = new PreConsume(
            $this->createInteropContextMock(),
            $this->createSubscriptionConsumerMock(),
            new NullLogger(),
            1,
            2,
            3
        );

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onPreConsume')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onPreConsume')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);
        $extensions->onPreConsume($context);
    }

    public function testShouldProxyOnPreReceiveToAllInternalExtensions()
    {
        $context = new MessageReceived(
            $this->createInteropContextMock(),
            $this->createMock(Consumer::class),
            $this->createMock(Message::class),
            $this->createMock(Processor::class),
            1,
            new NullLogger()
        );

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onMessageReceived')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onMessageReceived')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onMessageReceived($context);
    }

    public function testShouldProxyOnResultToAllInternalExtensions()
    {
        $context = new MessageResult(
            $this->createInteropContextMock(),
            $this->createMock(Message::class),
            'aResult',
            1,
            new NullLogger()
        );

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onResult')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onResult')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onResult($context);
    }

    public function testShouldProxyOnPostReceiveToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onPostReceived')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onPostReceived')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onPostReceived($context);
    }

    public function testShouldProxyOnIdleToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onIdle')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onIdle')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onIdle($context);
    }

    public function testShouldProxyOnInterruptedToAllInternalExtensions()
    {
        $context = $this->createContextMock();

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onInterrupted')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onInterrupted')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onInterrupted($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createLoggerMock(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createInteropContextMock(): \Interop\Queue\Context
    {
        return $this->createMock(\Interop\Queue\Context::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createInteropConsumerMock(): \Interop\Queue\Consumer
    {
        return $this->createMock(\Interop\Queue\Consumer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function createInteropProcessorMock(): \Interop\Queue\Processor
    {
        return $this->createMock(\Interop\Queue\Processor::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    protected function createContextMock()
    {
        return $this->createMock(Context::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ExtensionInterface
     */
    protected function createExtension()
    {
        return $this->createMock(ExtensionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createSubscriptionConsumerMock(): SubscriptionConsumer
    {
        return $this->createMock(SubscriptionConsumer::class);
    }
}
