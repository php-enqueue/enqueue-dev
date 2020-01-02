<?php

namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Consumption\Context\PostMessageReceived;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\PostMessageReceivedExtensionInterface;
use Enqueue\Consumption\Result;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Producer as InteropProducer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

class ReplyExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementPostMessageReceivedExtensionInterface()
    {
        $this->assertClassImplements(PostMessageReceivedExtensionInterface::class, ReplyExtension::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new ReplyExtension();
    }

    public function testShouldDoNothingIfReceivedMessageNotHaveReplyToSet()
    {
        $extension = new ReplyExtension();

        $postReceivedMessage = new PostMessageReceived(
            $this->createNeverUsedContextMock(),
            $this->createMock(Consumer::class),
            new NullMessage(),
            'aResult',
            1,
            new NullLogger()
        );

        $extension->onPostMessageReceived($postReceivedMessage);
    }

    public function testShouldDoNothingIfContextResultIsNotInstanceOfResult()
    {
        $extension = new ReplyExtension();

        $message = new NullMessage();
        $message->setReplyTo('aReplyToQueue');

        $postReceivedMessage = new PostMessageReceived(
            $this->createNeverUsedContextMock(),
            $this->createMock(Consumer::class),
            $message,
            'notInstanceOfResult',
            1,
            new NullLogger()
        );

        $extension->onPostMessageReceived($postReceivedMessage);
    }

    public function testShouldDoNothingIfResultInstanceOfResultButReplyMessageNotSet()
    {
        $extension = new ReplyExtension();

        $message = new NullMessage();
        $message->setReplyTo('aReplyToQueue');

        $postReceivedMessage = new PostMessageReceived(
            $this->createNeverUsedContextMock(),
            $this->createMock(Consumer::class),
            $message,
            Result::ack(),
            1,
            new NullLogger()
        );

        $extension->onPostMessageReceived($postReceivedMessage);
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

        /** @var MockObject|Context $contextMock */
        $contextMock = $this->createMock(Context::class);
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

        $postReceivedMessage = new PostMessageReceived(
            $contextMock,
            $this->createMock(Consumer::class),
            $message,
            Result::reply($replyMessage),
            1,
            new NullLogger()
        );

        $extension->onPostMessageReceived($postReceivedMessage);
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
    private function createNeverUsedContextMock(): Context
    {
        $contextMock = $this->createMock(Context::class);
        $contextMock
            ->expects($this->never())
            ->method('createProducer')
        ;

        return $contextMock;
    }
}
