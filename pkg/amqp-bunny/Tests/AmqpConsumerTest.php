<?php

namespace Enqueue\AmqpBunny\Tests;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use Bunny\Protocol\MethodBasicConsumeOkFrame;
use Enqueue\AmqpBunny\AmqpConsumer;
use Enqueue\AmqpBunny\Buffer;
use Enqueue\Null\NullMessage;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\WriteAttributeTrait;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
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
        $bunnyMessage = new Message('', 'delivery-tag', true, '', '', [], 'body');

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('get')
            ->willReturn($bunnyMessage)
        ;
        $channel
            ->expects($this->once())
            ->method('ack')
            ->with($this->identicalTo($bunnyMessage))
        ;

        $consumer = new AmqpConsumer($channel, new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $message = $consumer->receiveNoWait();

        // guard
        $this->assertSame('delivery-tag', $message->getDeliveryTag());

        $consumer->acknowledge($message);
    }

    public function testOnRejectShouldRejectMessage()
    {
        $bunnyMessage = new Message('', 'delivery-tag', true, '', '', [], 'body');

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('get')
            ->willReturn($bunnyMessage)
        ;
        $channel
            ->expects($this->once())
            ->method('reject')
            ->with($this->identicalTo($bunnyMessage), $this->isFalse())
        ;

        $consumer = new AmqpConsumer($channel, new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $message = $consumer->receiveNoWait();

        // guard
        $this->assertSame('delivery-tag', $message->getDeliveryTag());

        $consumer->reject($message, false);
    }

    public function testOnRejectShouldRequeueMessage()
    {
        $bunnyMessage = new Message('', 'delivery-tag', true, '', '', [], 'body');

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('get')
            ->willReturn($bunnyMessage)
        ;
        $channel
            ->expects($this->once())
            ->method('reject')
            ->with($this->identicalTo($bunnyMessage), $this->isTrue())
        ;

        $consumer = new AmqpConsumer($channel, new AmqpQueue('aName'), new Buffer(), 'basic_get');

        $message = $consumer->receiveNoWait();

        // guard
        $this->assertSame('delivery-tag', $message->getDeliveryTag());

        $consumer->reject($message, true);
    }

    public function testShouldReturnMessageOnReceiveNoWait()
    {
        $bunnyMessage = new Message('', 'delivery-tag', true, '', '', [], 'body');

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('get')
            ->willReturn($bunnyMessage)
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
        $bunnyMessage = new Message('', 'delivery-tag', true, '', '', [], 'body');

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('get')
            ->willReturn($bunnyMessage)
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
        $frame = new MethodBasicConsumeOkFrame();
        $frame->consumerTag = 'theConsumerTag';

        $client = $this->createClientMock();
        $client
            ->expects($this->atLeastOnce())
            ->method('run')
        ;

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('consume')
            ->willReturn($frame)
        ;
        $channel
            ->expects($this->atLeastOnce())
            ->method('getClient')
            ->willReturn($client)
        ;

        $consumer = new AmqpConsumer($channel, new AmqpQueue('aName'), new Buffer(), 'basic_consume');

        $message = new AmqpMessage();
        $message->setDeliveryTag('delivery-tag');
        $consumer->receive(1234);

        $this->assertSame('theConsumerTag', $consumer->getConsumerTag());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Client
     */
    public function createClientMock()
    {
        return $this->createMock(Client::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Channel
     */
    public function createChannelMock()
    {
        return $this->createMock(Channel::class);
    }
}
