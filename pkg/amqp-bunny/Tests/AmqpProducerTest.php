<?php

namespace Enqueue\AmqpBunny\Tests;

use Bunny\Channel;
use Enqueue\AmqpBunny\AmqpContext;
use Enqueue\AmqpBunny\AmqpProducer;
use Enqueue\AmqpTools\DelayStrategy;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Amqp\AmqpMessage as InteropAmqpMessage;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use Interop\Queue\Destination;
use Interop\Queue\Exception\DeliveryDelayNotSupportedException;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use PHPUnit\Framework\TestCase;

class AmqpProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AmqpProducer($this->createBunnyChannelMock(), $this->createContextMock());
    }

    public function testShouldImplementQueueInteropProducerInterface()
    {
        $this->assertClassImplements(Producer::class, AmqpProducer::class);
    }

    public function testShouldThrowExceptionWhenDestinationTypeIsInvalid()
    {
        $producer = new AmqpProducer($this->createBunnyChannelMock(), $this->createContextMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Interop\Amqp\AmqpQueue but got');

        $producer->send($this->createDestinationMock(), new AmqpMessage());
    }

    public function testShouldThrowExceptionWhenMessageTypeIsInvalid()
    {
        $producer = new AmqpProducer($this->createBunnyChannelMock(), $this->createContextMock());

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Interop\Amqp\AmqpMessage but it is');

        $producer->send(new AmqpTopic('name'), $this->createMessageMock());
    }

    public function testShouldPublishMessageToTopic()
    {
        $channel = $this->createBunnyChannelMock();
        $channel
            ->expects($this->once())
            ->method('publish')
            ->with('body', [], 'topic', 'routing-key', false, false)
        ;

        $topic = new AmqpTopic('topic');

        $message = new AmqpMessage('body');
        $message->setRoutingKey('routing-key');

        $producer = new AmqpProducer($channel, $this->createContextMock());
        $producer->send($topic, $message);
    }

    public function testShouldPublishMessageToQueue()
    {
        $channel = $this->createBunnyChannelMock();
        $channel
            ->expects($this->once())
            ->method('publish')
            ->with('body', [], '', 'queue', false, false)
        ;

        $queue = new AmqpQueue('queue');

        $producer = new AmqpProducer($channel, $this->createContextMock());
        $producer->send($queue, new AmqpMessage('body'));
    }

    public function testShouldDelayMessage()
    {
        $channel = $this->createBunnyChannelMock();
        $channel
            ->expects($this->never())
            ->method('publish')
        ;

        $message = new AmqpMessage('body');
        $context = $this->createContextMock();
        $queue = new AmqpQueue('queue');

        $delayStrategy = $this->createDelayStrategyMock();
        $delayStrategy
            ->expects($this->once())
            ->method('delayMessage')
            ->with($this->identicalTo($context), $this->identicalTo($queue), $this->identicalTo($message), 10000)
        ;

        $producer = new AmqpProducer($channel, $context);
        $producer->setDelayStrategy($delayStrategy);
        $producer->setDeliveryDelay(10000);

        $producer->send($queue, $message);
    }

    public function testShouldThrowExceptionOnSetDeliveryDelayWhenDeliveryStrategyIsNotSet()
    {
        $channel = $this->createBunnyChannelMock();
        $channel
            ->expects($this->never())
            ->method('publish')
        ;

        $producer = new AmqpProducer($channel, $this->createContextMock());

        $this->expectException(DeliveryDelayNotSupportedException::class);
        $this->expectExceptionMessage('The provider does not support delivery delay feature');

        $producer->setDeliveryDelay(10000);
    }

    public function testShouldSetMessageHeaders()
    {
        $channel = $this->createBunnyChannelMock();
        $channel
            ->expects($this->once())
            ->method('publish')
            ->with($this->anything(), ['misc' => 'text/plain'])
        ;

        $producer = new AmqpProducer($channel, $this->createContextMock());
        $producer->send(new AmqpTopic('name'), new AmqpMessage('body', [], ['misc' => 'text/plain']));
    }

    public function testShouldConvertStandartHeadersToBunnyFormat()
    {
        $channel = $this->createBunnyChannelMock();
        $expectedHeaders = [
            'content-encoding' => 'utf8',
            'content-type' => 'text/plain',
            'message-id' => 'id',
            'correlation-id' => 'correlation',
            'reply-to' => 'reply',
            'delivery-mode' => 2,
        ];
        $channel
            ->expects($this->once())
            ->method('publish')
            ->with($this->anything(), $expectedHeaders);

        $producer = new AmqpProducer($channel, $this->createContextMock());
        $message = new AmqpMessage('body', []);
        $message->setMessageId('id');
        $message->setReplyTo('reply');
        $message->setDeliveryMode(2);
        $message->setContentType('text/plain');
        $message->setContentEncoding('utf8');
        $message->setCorrelationId('correlation');

        $producer->send(new AmqpTopic('name'), $message);
    }

    public function testShouldSetMessageProperties()
    {
        $channel = $this->createBunnyChannelMock();
        $channel
            ->expects($this->once())
            ->method('publish')
            ->with($this->anything(), ['application_headers' => ['key' => 'value']])
        ;

        $producer = new AmqpProducer($channel, $this->createContextMock());
        $producer->send(new AmqpTopic('name'), new AmqpMessage('body', ['key' => 'value']));
    }

    public function testShouldPropagateFlags()
    {
        $channel = $this->createBunnyChannelMock();
        $channel
            ->expects($this->once())
            ->method('publish')
            ->with($this->anything(), $this->anything(), $this->anything(), $this->anything(), true, true)
        ;

        $message = new AmqpMessage('body');
        $message->addFlag(InteropAmqpMessage::FLAG_IMMEDIATE);
        $message->addFlag(InteropAmqpMessage::FLAG_MANDATORY);

        $producer = new AmqpProducer($channel, $this->createContextMock());
        $producer->send(new AmqpTopic('name'), $message);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Message
     */
    private function createMessageMock()
    {
        return $this->createMock(Message::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Destination
     */
    private function createDestinationMock()
    {
        return $this->createMock(Destination::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Channel
     */
    private function createBunnyChannelMock()
    {
        return $this->createMock(Channel::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpContext
     */
    private function createContextMock()
    {
        return $this->createPartialMock(AmqpContext::class, []);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DelayStrategy
     */
    private function createDelayStrategyMock()
    {
        return $this->createMock(DelayStrategy::class);
    }
}
