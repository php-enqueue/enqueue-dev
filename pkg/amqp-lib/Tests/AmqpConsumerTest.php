<?php

namespace Enqueue\AmqpLib\Tests;

use Enqueue\AmqpLib\AmqpConsumer;
use Enqueue\AmqpLib\AmqpMessage;
use Enqueue\AmqpLib\AmqpQueue;
use Enqueue\AmqpLib\Buffer;
use Enqueue\Null\NullMessage;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\WriteAttributeTrait;
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
            $this->createChannelMock(),
            new AmqpQueue('aName'),
            new Buffer(),
            'basic_get'
        );
    }

    public function testShouldReturnQueue()
    {
        $queue = new AmqpQueue('aName');

        $consumer = new AmqpConsumer($this->createChannelMock(), $queue, new Buffer(), 'basic_get');

        $this->assertSame($queue, $consumer->getQueue());
    }

    public function testOnAcknowledgeShouldThrowExceptionIfNotAmqpMessage()
    {
        $consumer = new AmqpConsumer($this->createChannelMock(), new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\AmqpLib\AmqpMessage but');

        $consumer->acknowledge(new NullMessage());
    }

    public function testOnRejectShouldThrowExceptionIfNotAmqpMessage()
    {
        $consumer = new AmqpConsumer($this->createChannelMock(), new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\AmqpLib\AmqpMessage but');

        $consumer->reject(new NullMessage());
    }

    public function testOnAcknowledgeShouldAcknowledgeMessage()
    {
        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('basic_ack')
            ->with('delivery-tag')
        ;

        $consumer = new AmqpConsumer($channel, new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $message = new AmqpMessage();
        $message->setDeliveryTag('delivery-tag');

        $consumer->acknowledge($message);
    }

    public function testOnRejectShouldRejectMessage()
    {
        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('basic_reject')
            ->with('delivery-tag', $this->isTrue())
        ;

        $consumer = new AmqpConsumer($channel, new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $message = new AmqpMessage();
        $message->setDeliveryTag('delivery-tag');

        $consumer->reject($message, true);
    }

    public function testShouldReturnMessageOnReceiveNoWait()
    {
        $amqpMessage = new \PhpAmqpLib\Message\AMQPMessage('body');
        $amqpMessage->delivery_info['delivery_tag'] = 'delivery-tag';
        $amqpMessage->delivery_info['redelivered'] = true;

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('basic_get')
            ->willReturn($amqpMessage)
        ;

        $consumer = new AmqpConsumer($channel, new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $message = new AmqpMessage();
        $message->setDeliveryTag('delivery-tag');

        $message = $consumer->receiveNoWait();

        $this->assertInstanceOf(AmqpMessage::class, $message);
        $this->assertSame('body', $message->getBody());
        $this->assertSame('delivery-tag', $message->getDeliveryTag());
        $this->assertTrue($message->isRedelivered());
    }

    public function testShouldReturnMessageOnReceiveWithReceiveMethodBasicGet()
    {
        $amqpMessage = new \PhpAmqpLib\Message\AMQPMessage('body');
        $amqpMessage->delivery_info['delivery_tag'] = 'delivery-tag';
        $amqpMessage->delivery_info['redelivered'] = true;

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('basic_get')
            ->willReturn($amqpMessage)
        ;

        $consumer = new AmqpConsumer($channel, new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $message = new AmqpMessage();
        $message->setDeliveryTag('delivery-tag');

        $message = $consumer->receive();

        $this->assertInstanceOf(AmqpMessage::class, $message);
        $this->assertSame('body', $message->getBody());
        $this->assertSame('delivery-tag', $message->getDeliveryTag());
        $this->assertTrue($message->isRedelivered());
    }

    public function testShouldCallExpectedMethodsWhenReceiveWithBasicConsumeMethod()
    {
        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('basic_consume')
            ->willReturn('consumer-tag')
        ;
        $channel
            ->expects($this->once())
            ->method('basic_qos')
            ->with($this->identicalTo(0), $this->identicalTo(1), $this->isFalse())
        ;
        $channel
            ->expects($this->once())
            ->method('wait')
        ;

        $consumer = new AmqpConsumer($channel, new AmqpQueue('aName'), new Buffer(), 'basic_consume');

        $message = new AmqpMessage();
        $message->setDeliveryTag('delivery-tag');
        $consumer->receive();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AMQPChannel
     */
    public function createChannelMock()
    {
        return $this->createMock(AMQPChannel::class);
    }
}
