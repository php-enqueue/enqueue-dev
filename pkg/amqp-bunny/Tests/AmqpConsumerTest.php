<?php

namespace Enqueue\AmqpBunny\Tests;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use Enqueue\AmqpBunny\AmqpConsumer;
use Enqueue\AmqpBunny\AmqpContext;
use Enqueue\Null\NullMessage;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\WriteAttributeTrait;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AmqpConsumerTest extends TestCase
{
    use ClassExtensionTrait;
    use WriteAttributeTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(Consumer::class, AmqpConsumer::class);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCouldBeConstructedWithContextAndQueueAsArguments()
    {
        new AmqpConsumer($this->createContextMock(), new AmqpQueue('aName'));
    }

    public function testShouldReturnQueue()
    {
        $queue = new AmqpQueue('aName');

        $consumer = new AmqpConsumer($this->createContextMock(), $queue);

        $this->assertSame($queue, $consumer->getQueue());
    }

    public function testOnAcknowledgeShouldThrowExceptionIfNotAmqpMessage()
    {
        $consumer = new AmqpConsumer($this->createContextMock(), new AmqpQueue('aName'));

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Interop\Amqp\AmqpMessage but');

        $consumer->acknowledge(new NullMessage());
    }

    public function testOnRejectShouldThrowExceptionIfNotAmqpMessage()
    {
        $consumer = new AmqpConsumer($this->createContextMock(), new AmqpQueue('aName'));

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Interop\Amqp\AmqpMessage but');

        $consumer->reject(new NullMessage());
    }

    public function testOnAcknowledgeShouldAcknowledgeMessage()
    {
        $channel = $this->createBunnyChannelMock();
        $channel
            ->expects($this->once())
            ->method('ack')
            ->with($this->isInstanceOf(Message::class))
            ->willReturnCallback(function (Message $message) {
                $this->assertSame(145, $message->deliveryTag);
            });

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getBunnyChannel')
            ->willReturn($channel)
        ;

        $consumer = new AmqpConsumer($context, new AmqpQueue('aName'));

        $message = new AmqpMessage();
        $message->setDeliveryTag(145);

        $consumer->acknowledge($message);
    }

    public function testOnRejectShouldRejectMessage()
    {
        $channel = $this->createBunnyChannelMock();
        $channel
            ->expects($this->once())
            ->method('reject')
            ->with($this->isInstanceOf(Message::class), false)
            ->willReturnCallback(function (Message $message) {
                $this->assertSame(167, $message->deliveryTag);
            });

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getBunnyChannel')
            ->willReturn($channel)
        ;

        $consumer = new AmqpConsumer($context, new AmqpQueue('aName'));

        $message = new AmqpMessage();
        $message->setDeliveryTag(167);

        $consumer->reject($message, false);
    }

    public function testOnRejectShouldRequeueMessage()
    {
        $channel = $this->createBunnyChannelMock();
        $channel
            ->expects($this->once())
            ->method('reject')
            ->with($this->isInstanceOf(Message::class), true)
            ->willReturnCallback(function (Message $message) {
                $this->assertSame(178, $message->deliveryTag);
            });

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getBunnyChannel')
            ->willReturn($channel)
        ;

        $consumer = new AmqpConsumer($context, new AmqpQueue('aName'));

        $message = new AmqpMessage();
        $message->setDeliveryTag(178);

        $consumer->reject($message, true);
    }

    public function testShouldReturnMessageOnReceiveNoWait()
    {
        $bunnyMessage = new Message('', 'delivery-tag', true, '', '', [], 'body');

        $message = new AmqpMessage();

        $channel = $this->createBunnyChannelMock();
        $channel
            ->expects($this->once())
            ->method('get')
            ->willReturn($bunnyMessage)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getBunnyChannel')
            ->willReturn($channel)
        ;
        $context
            ->expects($this->once())
            ->method('convertMessage')
            ->with($this->identicalTo($bunnyMessage))
            ->willReturn($message)
        ;

        $consumer = new AmqpConsumer($context, new AmqpQueue('aName'));

        $receivedMessage = $consumer->receiveNoWait();

        $this->assertSame($message, $receivedMessage);
    }

    public function testShouldReturnMessageOnReceiveWithReceiveMethodBasicGet()
    {
        $bunnyMessage = new Message('', 'delivery-tag', true, '', '', [], 'body');

        $message = new AmqpMessage();

        $channel = $this->createBunnyChannelMock();
        $channel
            ->expects($this->once())
            ->method('get')
            ->willReturn($bunnyMessage)
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getBunnyChannel')
            ->willReturn($channel)
        ;
        $context
            ->expects($this->once())
            ->method('convertMessage')
            ->with($this->identicalTo($bunnyMessage))
            ->willReturn($message)
        ;

        $consumer = new AmqpConsumer($context, new AmqpQueue('aName'));

        $receivedMessage = $consumer->receive();

        $this->assertSame($message, $receivedMessage);
    }

    /**
     * @return MockObject|Client
     */
    public function createClientMock()
    {
        return $this->createMock(Client::class);
    }

    /**
     * @return MockObject|AmqpContext
     */
    public function createContextMock()
    {
        return $this->createMock(AmqpContext::class);
    }

    /**
     * @return MockObject|Channel
     */
    public function createBunnyChannelMock()
    {
        return $this->createMock(Channel::class);
    }
}
