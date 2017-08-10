<?php

namespace Enqueue\AmqpLib\Tests;

use Enqueue\AmqpLib\AmqpConsumer;
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
        $this->expectExceptionMessage('The message must be an instance of Interop\Amqp\AmqpMessage but');

        $consumer->acknowledge(new NullMessage());
    }

    public function testOnRejectShouldThrowExceptionIfNotAmqpMessage()
    {
        $consumer = new AmqpConsumer($this->createChannelMock(), new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Interop\Amqp\AmqpMessage but');

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
        $amqpMessage->delivery_info['routing_key'] = 'routing-key';
        $amqpMessage->delivery_info['redelivered'] = true;
        $amqpMessage->delivery_info['routing_key'] = 'routing-key';

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
        $this->assertSame('routing-key', $message->getRoutingKey());
        $this->assertTrue($message->isRedelivered());
    }

    public function testShouldReturnMessageOnReceiveWithReceiveMethodBasicGet()
    {
        $amqpMessage = new \PhpAmqpLib\Message\AMQPMessage('body');
        $amqpMessage->delivery_info['delivery_tag'] = 'delivery-tag';
        $amqpMessage->delivery_info['routing_key'] = 'routing-key';
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
        $this->assertSame('routing-key', $message->getRoutingKey());
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
            ->method('wait')
            ->willReturnCallback(function () {
                usleep(2000);
            });

        $consumer = new AmqpConsumer($channel, new AmqpQueue('aName'), new Buffer(), 'basic_consume');

        $message = new AmqpMessage();
        $message->setDeliveryTag('delivery-tag');
        $consumer->receive(1);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AMQPChannel
     */
    public function createChannelMock()
    {
        return $this->createMock(AMQPChannel::class);
    }
}
