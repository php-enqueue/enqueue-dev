<?php

namespace Enqueue\Tests\Rpc;

use Enqueue\NoEffect\NullContext;
use Enqueue\NoEffect\NullMessage;
use Enqueue\NoEffect\NullQueue;
use Enqueue\Rpc\Promise;
use Enqueue\Rpc\RpcClient;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Producer as InteropProducer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class RpcClientTest extends TestCase
{
    public function testCouldBeConstructedWithContextAsFirstArgument()
    {
        new RpcClient($this->createContextMock());
    }

    public function testShouldSetReplyToIfNotSet()
    {
        $context = new NullContext();
        $queue = $context->createQueue('rpc.call');
        $message = $context->createMessage();

        $rpc = new RpcClient($context);

        $rpc->callAsync($queue, $message, 2);

        $this->assertNotEmpty($message->getReplyTo());
    }

    public function testShouldNotSetReplyToIfSet()
    {
        $context = new NullContext();
        $queue = $context->createQueue('rpc.call');
        $message = $context->createMessage();
        $message->setReplyTo('rpc.reply');

        $rpc = new RpcClient($context);

        $rpc->callAsync($queue, $message, 2);

        $this->assertEquals('rpc.reply', $message->getReplyTo());
    }

    public function testShouldSetCorrelationIdIfNotSet()
    {
        $context = new NullContext();
        $queue = $context->createQueue('rpc.call');
        $message = $context->createMessage();

        $rpc = new RpcClient($context);

        $rpc->callAsync($queue, $message, 2);

        $this->assertNotEmpty($message->getCorrelationId());
    }

    public function testShouldNotSetCorrelationIdIfSet()
    {
        $context = new NullContext();
        $queue = $context->createQueue('rpc.call');
        $message = $context->createMessage();
        $message->setCorrelationId('theCorrelationId');

        $rpc = new RpcClient($context);

        $rpc->callAsync($queue, $message, 2);

        $this->assertEquals('theCorrelationId', $message->getCorrelationId());
    }

    public function testShouldProduceMessageToQueue()
    {
        $queue = new NullQueue('aQueue');
        $message = new NullMessage();
        $message->setCorrelationId('theCorrelationId');
        $message->setReplyTo('theReplyTo');

        $producer = $this->createInteropProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($queue), $this->identicalTo($message))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer)
        ;

        $rpc = new RpcClient($context);

        $rpc->callAsync($queue, $message, 2);
    }

    public function testShouldReceiveMessageAndAckMessageIfCorrelationEquals()
    {
        $queue = new NullQueue('aQueue');
        $replyQueue = new NullQueue('theReplyTo');
        $message = new NullMessage();
        $message->setCorrelationId('theCorrelationId');
        $message->setReplyTo('theReplyTo');

        $receivedMessage = new NullMessage();
        $receivedMessage->setCorrelationId('theCorrelationId');

        $consumer = $this->createConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('receive')
            ->with(12345)
            ->willReturn($receivedMessage)
        ;
        $consumer
            ->expects($this->once())
            ->method('acknowledge')
            ->with($this->identicalTo($receivedMessage))
        ;
        $consumer
            ->expects($this->never())
            ->method('reject')
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($this->createInteropProducerMock())
        ;
        $context
            ->expects($this->atLeastOnce())
            ->method('createQueue')
            ->with('theReplyTo')
            ->willReturn($replyQueue)
        ;
        $context
            ->expects($this->once())
            ->method('createConsumer')
            ->with($this->identicalTo($replyQueue))
            ->willReturn($consumer)
        ;

        $rpc = new RpcClient($context);

        $rpc->callAsync($queue, $message, 2)->receive(12345);
    }

    public function testShouldReceiveNoWaitMessageAndAckMessageIfCorrelationEquals()
    {
        $queue = new NullQueue('aQueue');
        $replyQueue = new NullQueue('theReplyTo');
        $message = new NullMessage();
        $message->setCorrelationId('theCorrelationId');
        $message->setReplyTo('theReplyTo');

        $receivedMessage = new NullMessage();
        $receivedMessage->setCorrelationId('theCorrelationId');

        $consumer = $this->createConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('receiveNoWait')
            ->willReturn($receivedMessage)
        ;
        $consumer
            ->expects($this->once())
            ->method('acknowledge')
            ->with($this->identicalTo($receivedMessage))
        ;
        $consumer
            ->expects($this->never())
            ->method('reject')
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($this->createInteropProducerMock())
        ;
        $context
            ->expects($this->atLeastOnce())
            ->method('createQueue')
            ->with('theReplyTo')
            ->willReturn($replyQueue)
        ;
        $context
            ->expects($this->once())
            ->method('createConsumer')
            ->with($this->identicalTo($replyQueue))
            ->willReturn($consumer)
        ;

        $rpc = new RpcClient($context);

        $rpc->callAsync($queue, $message, 2)->receiveNoWait();
    }

    public function testShouldDeleteQueueAfterReceiveIfDeleteReplyQueueIsTrue()
    {
        $queue = new NullQueue('aQueue');
        $replyQueue = new NullQueue('theReplyTo');
        $message = new NullMessage();
        $message->setCorrelationId('theCorrelationId');
        $message->setReplyTo('theReplyTo');

        $receivedMessage = new NullMessage();
        $receivedMessage->setCorrelationId('theCorrelationId');

        $consumer = $this->createConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('receive')
            ->willReturn($receivedMessage)
        ;

        $context = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteQueue'])
            ->getMockForAbstractClass()
        ;

        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($this->createInteropProducerMock())
        ;
        $context
            ->expects($this->atLeastOnce())
            ->method('createQueue')
            ->with('theReplyTo')
            ->willReturn($replyQueue)
        ;
        $context
            ->expects($this->once())
            ->method('createConsumer')
            ->with($this->identicalTo($replyQueue))
            ->willReturn($consumer)
        ;
        $context
            ->expects($this->once())
            ->method('deleteQueue')
            ->with($this->identicalTo($replyQueue))
        ;

        $rpc = new RpcClient($context);

        $promise = $rpc->callAsync($queue, $message, 2);
        $promise->setDeleteReplyQueue(true);
        $promise->receive();
    }

    public function testShouldNotCallDeleteQueueIfDeleteReplyQueueIsTrueButContextHasNoDeleteQueueMethod()
    {
        $queue = new NullQueue('aQueue');
        $replyQueue = new NullQueue('theReplyTo');
        $message = new NullMessage();
        $message->setCorrelationId('theCorrelationId');
        $message->setReplyTo('theReplyTo');

        $receivedMessage = new NullMessage();
        $receivedMessage->setCorrelationId('theCorrelationId');

        $consumer = $this->createConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('receive')
            ->willReturn($receivedMessage)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($this->createInteropProducerMock())
        ;
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with('theReplyTo')
            ->willReturn($replyQueue)
        ;
        $context
            ->expects($this->once())
            ->method('createConsumer')
            ->with($this->identicalTo($replyQueue))
            ->willReturn($consumer)
        ;

        $rpc = new RpcClient($context);

        $promise = $rpc->callAsync($queue, $message, 2);
        $promise->setDeleteReplyQueue(true);

        $promise->receive();
    }

    public function testShouldDoSyncCall()
    {
        $timeout = 123;
        $message = new NullMessage();
        $queue = new NullQueue('aName');
        $replyMessage = new NullMessage();

        $promiseMock = $this->createMock(Promise::class);
        $promiseMock
            ->expects($this->once())
            ->method('receive')
            ->willReturn($replyMessage)
        ;

        $rpc = $this->getMockBuilder(RpcClient::class)->disableOriginalConstructor()->setMethods(['callAsync'])->getMock();
        $rpc
            ->expects($this->once())
            ->method('callAsync')
            ->with($this->identicalTo($queue), $this->identicalTo($message), $timeout)
            ->willReturn($promiseMock)
        ;

        $actualReplyMessage = $rpc->call($queue, $message, $timeout);

        $this->assertSame($replyMessage, $actualReplyMessage);
    }

    /**
     * @return Context|MockObject|InteropProducer
     */
    private function createInteropProducerMock()
    {
        return $this->createMock(InteropProducer::class);
    }

    /**
     * @return MockObject|Consumer
     */
    private function createConsumerMock()
    {
        return $this->createMock(Consumer::class);
    }

    /**
     * @return MockObject|Context
     */
    private function createContextMock()
    {
        return $this->createMock(Context::class);
    }
}
