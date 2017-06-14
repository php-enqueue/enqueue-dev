<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\RpcClient;
use Enqueue\Null\NullContext;
use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Psr\PsrConsumer;
use Enqueue\Psr\PsrContext;
use Enqueue\Rpc\Promise;
use PHPUnit\Framework\TestCase;

class RpcClientTest extends TestCase
{
    public function testCouldBeConstructedWithPsrContextDriverAndProducerAsArguments()
    {
        new RpcClient(
            $this->createProducerMock(),
            $this->createPsrContextMock()
        );
    }

    public function testShouldSetReplyToIfNotSet()
    {
        $context = new NullContext();

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function ($topic, Message $message) {
                $this->assertNotEmpty($message->getReplyTo());
            })
        ;

        $rpc = new RpcClient(
            $producerMock,
            $context
        );

        $rpc->callAsync('aTopic', new Message(), 2);
    }

    public function testShouldNotSetReplyToIfSet()
    {
        $context = new NullContext();

        $message = new Message();
        $message->setReplyTo('theReplyTo');

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function ($topic, Message $message) {
                $this->assertEquals('theReplyTo', $message->getReplyTo());
            })
        ;

        $rpc = new RpcClient(
            $producerMock,
            $context
        );

        $rpc->callAsync('aTopic', $message, 2);
    }

    public function testShouldUseSameTopicOnProducerSendCall()
    {
        $context = new NullContext();

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function ($topic) {
                $this->assertEquals('theTopic', $topic);
            })
        ;

        $rpc = new RpcClient(
            $producerMock,
            $context
        );

        $rpc->callAsync('theTopic', new Message(), 2);
    }

    public function testShouldSetCorrelationIdIfNotSet()
    {
        $context = new NullContext();

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function ($topic, Message $message) {
                $this->assertNotEmpty($message->getCorrelationId());
            })
        ;

        $rpc = new RpcClient(
            $producerMock,
            $context
        );

        $rpc->callAsync('aTopic', new Message(), 2);
    }

    public function testShouldNotSetCorrelationIdIfSet()
    {
        $context = new NullContext();

        $message = new Message();
        $message->setCorrelationId('theCorrelationId');

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->willReturnCallback(function ($topic, Message $message) {
                $this->assertEquals('theCorrelationId', $message->getCorrelationId());
            })
        ;

        $rpc = new RpcClient(
            $producerMock,
            $context
        );

        $rpc->callAsync('aTopic', $message, 2);
    }

    public function testShouldDoSyncCall()
    {
        $timeout = 123;
        $replyMessage = new NullMessage();

        $promiseMock = $this->createMock(Promise::class);
        $promiseMock
            ->expects($this->once())
            ->method('receive')
            ->willReturn($replyMessage)
        ;

        /** @var \PHPUnit_Framework_MockObject_MockObject|RpcClient $rpc */
        $rpc = $this->getMockBuilder(RpcClient::class)->disableOriginalConstructor()->setMethods(['callAsync'])->getMock();
        $rpc
            ->expects($this->once())
            ->method('callAsync')
            ->with('theTopic', 'theMessage', $timeout)
            ->willReturn($promiseMock)
        ;

        $actualReplyMessage = $rpc->call('theTopic', 'theMessage', $timeout);

        $this->assertSame($replyMessage, $actualReplyMessage);
    }

    public function testShouldReceiveMessageAndAckMessageIfCorrelationEquals()
    {
        $replyQueue = new NullQueue('theReplyTo');
        $message = new Message();
        $message->setCorrelationId('theCorrelationId');
        $message->setReplyTo('theReplyTo');

        $receivedMessage = new NullMessage();
        $receivedMessage->setCorrelationId('theCorrelationId');

        $consumer = $this->createPsrConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('receive')
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

        $context = $this->createPsrContextMock();
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

        $rpc = new RpcClient($this->createProducerMock(), $context);

        $rpc->callAsync('topic', $message, 2)->receive();
    }

    public function testShouldReceiveNoWaitMessageAndAckMessageIfCorrelationEquals()
    {
        $replyQueue = new NullQueue('theReplyTo');
        $message = new Message();
        $message->setCorrelationId('theCorrelationId');
        $message->setReplyTo('theReplyTo');

        $receivedMessage = new NullMessage();
        $receivedMessage->setCorrelationId('theCorrelationId');

        $consumer = $this->createPsrConsumerMock();
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

        $context = $this->createPsrContextMock();
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

        $rpc = new RpcClient($this->createProducerMock(), $context);

        $rpc->callAsync('topic', $message, 2)->receiveNoWait();
    }

    public function testShouldDeleteQueueAfterReceiveIfDeleteReplyQueueIsTrue()
    {
        $replyQueue = new NullQueue('theReplyTo');
        $message = new Message();
        $message->setCorrelationId('theCorrelationId');
        $message->setReplyTo('theReplyTo');

        $receivedMessage = new NullMessage();
        $receivedMessage->setCorrelationId('theCorrelationId');

        $consumer = $this->createPsrConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('receive')
            ->willReturn($receivedMessage)
        ;

        $context = $this->getMockBuilder(PsrContext::class)
            ->disableOriginalConstructor()
            ->setMethods(['deleteQueue'])
            ->getMockForAbstractClass()
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
        $context
            ->expects($this->once())
            ->method('deleteQueue')
            ->with($this->identicalTo($replyQueue))
        ;

        $rpc = new RpcClient($this->createProducerMock(), $context);

        $promise = $rpc->callAsync('topic', $message, 2);
        $promise->setDeleteReplyQueue(true);
        $promise->receive();
    }

    public function testShouldNotCallDeleteQueueIfDeleteReplyQueueIsTrueButContextHasNoDeleteQueueMethod()
    {
        $replyQueue = new NullQueue('theReplyTo');
        $message = new Message();
        $message->setCorrelationId('theCorrelationId');
        $message->setReplyTo('theReplyTo');

        $receivedMessage = new NullMessage();
        $receivedMessage->setCorrelationId('theCorrelationId');

        $consumer = $this->createPsrConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('receive')
            ->willReturn($receivedMessage)
        ;

        $context = $this->createPsrContextMock();
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

        $rpc = new RpcClient($this->createProducerMock(), $context);

        $promise = $rpc->callAsync('topic', $message, 2);
        $promise->setDeleteReplyQueue(true);

        $promise->receive();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrContext
     */
    private function createPsrContextMock()
    {
        return $this->createMock(PsrContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProducerInterface
     */
    private function createProducerMock()
    {
        return $this->createMock(ProducerInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrConsumer
     */
    private function createPsrConsumerMock()
    {
        return $this->createMock(PsrConsumer::class);
    }
}
