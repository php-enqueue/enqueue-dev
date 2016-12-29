<?php
namespace Enqueue\Tests\Consumption\Extension;

use Enqueue\Psr\Context as PsrContext;
use Enqueue\Psr\Producer;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\Result;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Transport\Null\NullContext;
use Enqueue\Transport\Null\NullMessage;
use Enqueue\Transport\Null\NullQueue;

class ReplyExtensionTest extends \PHPUnit_Framework_TestCase
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

        $extension->onPreReceived(new Context(new NullContext()));
    }

    public function testShouldDoNothingOnStart()
    {
        $extension = new ReplyExtension();

        $extension->onStart(new Context(new NullContext()));
    }

    public function testShouldDoNothingOnBeforeReceive()
    {
        $extension = new ReplyExtension();

        $extension->onBeforeReceive(new Context(new NullContext()));
    }

    public function testShouldDoNothingOnInterrupted()
    {
        $extension = new ReplyExtension();

        $extension->onInterrupted(new Context(new NullContext()));
    }

    public function testShouldDoNothingIfReceivedMessageNotHaveReplyToSet()
    {
        $extension = new ReplyExtension();

        $context = new Context(new NullContext());
        $context->setPsrMessage(new NullMessage());

        $extension->onPostReceived($context);
    }

    public function testThrowIfResultNotInstanceOfResult()
    {
        $extension = new ReplyExtension();

        $message = new NullMessage();
        $message->setReplyTo('aReplyToQueue');

        $context = new Context(new NullContext());
        $context->setPsrMessage($message);
        $context->setResult('notInstanceOfResult');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('To send a reply an instance of Result class has to returned from a MessageProcessor.');
        $extension->onPostReceived($context);
    }

    public function testThrowIfResultInstanceOfResultButReplyMessageNotSet()
    {
        $extension = new ReplyExtension();

        $message = new NullMessage();
        $message->setReplyTo('aReplyToQueue');

        $context = new Context(new NullContext());
        $context->setPsrMessage($message);
        $context->setResult(Result::ack());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('To send a reply the Result must contain a reply message.');
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

        $producerMock = $this->createMock(Producer::class);
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->with($replyQueue, $replyMessage)
        ;

        $contextMock = $this->createMock(PsrContext::class);
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
        $context->setPsrMessage($message);
        $context->setResult(Result::reply($replyMessage));

        $extension->onPostReceived($context);
    }
}
