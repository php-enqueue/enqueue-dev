<?php

namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Consumption\Context;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\Start;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\Result;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context as InteropContext;
use Interop\Queue\Producer as InteropProducer;
use Interop\Queue\SubscriptionConsumer;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ReplyExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementExtensionInterface()
    {
        $this->assertClassImplements(ExtensionInterface::class, ReplyExtension::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new ReplyExtension();
    }

    public function testShouldDoNothingOnPreReceived()
    {
        $extension = new ReplyExtension();

        $extension->onPreReceived(new Context($this->createNeverUsedContextMock()));
    }

    public function testShouldDoNothingOnStart()
    {
        $extension = new ReplyExtension();

        $extension->onStart(new Start($this->createNeverUsedContextMock(), new NullLogger(), [], 0, 0, 0));
    }

    public function testShouldDoNothingOnPreConsume()
    {
        $extension = new ReplyExtension();

        $extension->onPreConsume(new PreConsume(
            $this->createInteropContextMock(),
            $this->createSubscriptionConsumerMock(),
            new NullLogger(),
            1,
            2,
            3
        ));
    }

    public function testShouldDoNothingOnInterrupted()
    {
        $extension = new ReplyExtension();

        $extension->onInterrupted(new Context($this->createNeverUsedContextMock()));
    }

    public function testShouldDoNothingIfReceivedMessageNotHaveReplyToSet()
    {
        $extension = new ReplyExtension();

        $context = new Context($this->createNeverUsedContextMock());
        $context->setInteropMessage(new NullMessage());

        $extension->onPostReceived($context);
    }

    public function testShouldDoNothingIfContextResultIsNotInstanceOfResult()
    {
        $extension = new ReplyExtension();

        $message = new NullMessage();
        $message->setReplyTo('aReplyToQueue');

        $context = new Context($this->createNeverUsedContextMock());
        $context->setInteropMessage($message);
        $context->setResult('notInstanceOfResult');

        $extension->onPostReceived($context);
    }

    public function testShouldDoNothingIfResultInstanceOfResultButReplyMessageNotSet()
    {
        $extension = new ReplyExtension();

        $message = new NullMessage();
        $message->setReplyTo('aReplyToQueue');

        $context = new Context($this->createNeverUsedContextMock());
        $context->setInteropMessage($message);
        $context->setResult(Result::ack());

        $extension->onPostReceived($context);
    }

    public function testShouldSendReplyMessageToReplyQueueOnPostReceived()
    {
        $extension = new ReplyExtension();

        $message = new NullMessage();
        $message->setReplyTo('aReplyToQueue');
        $message->setCorrelationId('theCorrelationId');

        $replyMessage = new NullMessage();
        $replyMessage->setCorrelationId('theCorrelationId');

        $replyQueue = new NullQueue('aReplyName');

        $producerMock = $this->createMock(InteropProducer::class);
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->with($replyQueue, $replyMessage)
        ;

        /** @var \PHPUnit_Framework_MockObject_MockObject|InteropContext $contextMock */
        $contextMock = $this->createMock(InteropContext::class);
        $contextMock
            ->expects($this->once())
            ->method('createQueue')
            ->willReturn($replyQueue)
        ;
        $contextMock
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producerMock)
        ;

        $context = new Context($contextMock);
        $context->setInteropMessage($message);
        $context->setResult(Result::reply($replyMessage));
        $context->setLogger(new NullLogger());

        $extension->onPostReceived($context);
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
    private function createSubscriptionConsumerMock(): SubscriptionConsumer
    {
        return $this->createMock(SubscriptionConsumer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|InteropContext
     */
    private function createNeverUsedContextMock(): InteropContext
    {
        $contextMock = $this->createMock(InteropContext::class);
        $contextMock
            ->expects($this->never())
            ->method('createProducer')
        ;

        return $contextMock;
    }
}
