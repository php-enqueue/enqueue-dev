<?php

namespace Enqueue\Tests\Consumption;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\Context\End;
use Enqueue\Consumption\Context\InitLogger;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\MessageResult;
use Enqueue\Consumption\Context\PostConsume;
use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\PreSubscribe;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Interop\Queue\SubscriptionConsumer;
use PHPUnit\Framework\MockObject\MockObject;
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

    public function testShouldProxyOnInitLoggerToAllInternalExtensions()
    {
        $context = new InitLogger(new NullLogger());

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onInitLogger')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onInitLogger')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onInitLogger($context);
    }

    public function testShouldProxyOnStartToAllInternalExtensions()
    {
        $context = new Start($this->createInteropContextMock(), $this->createLoggerMock(), [], 0, 0);

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
            $this->createInteropConsumerMock(),
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
        $context = new PostMessageReceived(
            $this->createInteropContextMock(),
            $this->createMock(Consumer::class),
            $this->createMock(Message::class),
            'aResult',
            1,
            new NullLogger()
        );

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onPostMessageReceived')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onPostMessageReceived')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onPostMessageReceived($context);
    }

    public function testShouldProxyOnPostConsumeToAllInternalExtensions()
    {
        $postConsume = new PostConsume(
            $this->createInteropContextMock(),
            $this->createSubscriptionConsumerMock(),
            1,
            1,
            1,
            new NullLogger()
        );

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onPostConsume')
            ->with($this->identicalTo($postConsume))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onPostConsume')
            ->with($this->identicalTo($postConsume))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onPostConsume($postConsume);
    }

    public function testShouldProxyOnEndToAllInternalExtensions()
    {
        $context = new End($this->createInteropContextMock(), 1, 2, new NullLogger());

        $fooExtension = $this->createExtension();
        $fooExtension
            ->expects($this->once())
            ->method('onEnd')
            ->with($this->identicalTo($context))
        ;
        $barExtension = $this->createExtension();
        $barExtension
            ->expects($this->once())
            ->method('onEnd')
            ->with($this->identicalTo($context))
        ;

        $extensions = new ChainExtension([$fooExtension, $barExtension]);

        $extensions->onEnd($context);
    }

    /**
     * @return MockObject
     */
    protected function createLoggerMock(): LoggerInterface
    {
        return $this->createMock(LoggerInterface::class);
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
    protected function createInteropConsumerMock(): Consumer
    {
        return $this->createMock(Consumer::class);
    }

    /**
     * @return MockObject
     */
    protected function createInteropProcessorMock(): Processor
    {
        return $this->createMock(Processor::class);
    }

    /**
     * @return MockObject|ExtensionInterface
     */
    protected function createExtension()
    {
        return $this->createMock(ExtensionInterface::class);
    }

    /**
     * @return MockObject
     */
    private function createSubscriptionConsumerMock(): SubscriptionConsumer
    {
        return $this->createMock(SubscriptionConsumer::class);
    }
}
