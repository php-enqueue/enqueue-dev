<?php

namespace Enqueue\AmqpLib\Tests;

use Enqueue\AmqpLib\AmqpConsumer;
use Enqueue\AmqpLib\AmqpContext;
use Enqueue\AmqpLib\Buffer;
use Enqueue\Null\NullMessage;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\WriteAttributeTrait;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use PhpAmqpLib\Channel\AMQPChannel;
use PHPUnit\Framework\TestCase;

class AmqpConsumerTest extends TestCase
{
    use ClassExtensionTrait;
    use WriteAttributeTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(PsrConsumer::class, AmqpConsumer::class);
    }

    public function testCouldBeConstructedWithContextAndQueueAndBufferAsArguments()
    {
        new AmqpConsumer(
            $this->createContextMock(),
            new AmqpQueue('aName'),
            new Buffer(),
            'basic_get'
        );
    }

    public function testShouldReturnQueue()
    {
        $queue = new AmqpQueue('aName');

        $consumer = new AmqpConsumer($this->createContextMock(), $queue, new Buffer(), 'basic_get');

        $this->assertSame($queue, $consumer->getQueue());
    }

    public function testOnAcknowledgeShouldThrowExceptionIfNotAmqpMessage()
    {
        $consumer = new AmqpConsumer($this->createContextMock(), new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Interop\Amqp\AmqpMessage but');

        $consumer->acknowledge(new NullMessage());
    }

    public function testOnRejectShouldThrowExceptionIfNotAmqpMessage()
    {
        $consumer = new AmqpConsumer($this->createContextMock(), new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Interop\Amqp\AmqpMessage but');

        $consumer->reject(new NullMessage());
    }

    public function testOnAcknowledgeShouldAcknowledgeMessage()
    {
        $channel = $this->createLibChannelMock();
        $channel
            ->expects($this->once())
            ->method('basic_ack')
            ->with('delivery-tag')
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getLibChannel')
            ->willReturn($channel)
        ;

        $consumer = new AmqpConsumer($context, new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $message = new AmqpMessage();
        $message->setDeliveryTag('delivery-tag');

        $consumer->acknowledge($message);
    }

    public function testOnRejectShouldRejectMessage()
    {
        $channel = $this->createLibChannelMock();
        $channel
            ->expects($this->once())
            ->method('basic_reject')
            ->with('delivery-tag', $this->isTrue())
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getLibChannel')
            ->willReturn($channel)
        ;

        $consumer = new AmqpConsumer($context, new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $message = new AmqpMessage();
        $message->setDeliveryTag('delivery-tag');

        $consumer->reject($message, true);
    }

    public function testShouldReturnMessageOnReceiveNoWait()
    {
        $libMessage = new \PhpAmqpLib\Message\AMQPMessage('body');
        $libMessage->delivery_info['delivery_tag'] = 'delivery-tag';
        $libMessage->delivery_info['routing_key'] = 'routing-key';
        $libMessage->delivery_info['redelivered'] = true;
        $libMessage->delivery_info['routing_key'] = 'routing-key';

        $message = new AmqpMessage();

        $channel = $this->createLibChannelMock();
        $channel
            ->expects($this->once())
            ->method('basic_get')
            ->willReturn($libMessage)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getLibChannel')
            ->willReturn($channel)
        ;
        $context
            ->expects($this->once())
            ->method('convertMessage')
            ->with($this->identicalTo($libMessage))
            ->willReturn($message)
        ;

        $consumer = new AmqpConsumer($context, new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $receivedMessage = $consumer->receiveNoWait();

        $this->assertSame($message, $receivedMessage);
    }

    public function testShouldReturnMessageOnReceiveWithReceiveMethodBasicGet()
    {
        $libMessage = new \PhpAmqpLib\Message\AMQPMessage('body');
        $libMessage->delivery_info['delivery_tag'] = 'delivery-tag';
        $libMessage->delivery_info['routing_key'] = 'routing-key';
        $libMessage->delivery_info['redelivered'] = true;

        $message = new AmqpMessage();

        $channel = $this->createLibChannelMock();
        $channel
            ->expects($this->once())
            ->method('basic_get')
            ->willReturn($libMessage)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getLibChannel')
            ->willReturn($channel)
        ;
        $context
            ->expects($this->once())
            ->method('convertMessage')
            ->with($this->identicalTo($libMessage))
            ->willReturn($message)
        ;

        $consumer = new AmqpConsumer($context, new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $receivedMessage = $consumer->receive();

        $this->assertSame($message, $receivedMessage);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpContext
     */
    public function createContextMock()
    {
        return $this->createMock(AmqpContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AMQPChannel
     */
    public function createLibChannelMock()
    {
        return $this->createMock(AMQPChannel::class);
    }
}
