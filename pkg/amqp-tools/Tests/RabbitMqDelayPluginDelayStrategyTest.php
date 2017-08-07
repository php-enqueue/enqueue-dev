<?php

namespace Enqueue\AmqpTools\Tests;

use Enqueue\AmqpTools\DelayStrategy;
use Enqueue\AmqpTools\RabbitMqDelayPluginDelayStrategy;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Amqp\AmqpBind;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpProducer;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use PHPUnit\Framework\TestCase;

class RabbitMqDelayPluginDelayStrategyTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementDelayStrategyInterface()
    {
        $this->assertClassImplements(DelayStrategy::class, RabbitMqDelayPluginDelayStrategy::class);
    }

    public function testShouldSendDelayedMessageToTopic()
    {
        $delayedTopic = new AmqpTopic('the-topic');
        $delayedMessage = new AmqpMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($delayedTopic), $this->identicalTo($delayedMessage))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createTopic')
            ->with($this->identicalTo('enqueue.the-topic.delayed'))
            ->willReturn($delayedTopic)
        ;
        $context
            ->expects($this->once())
            ->method('declareTopic')
            ->with($this->identicalTo($delayedTopic))
        ;
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer)
        ;
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->with($this->identicalTo('the body'), $this->identicalTo(['key' => 'value']), $this->identicalTo(['hkey' => 'hvalue']))
            ->willReturn($delayedMessage)
        ;
        $context
            ->expects($this->once())
            ->method('bind')
            ->with($this->isInstanceOf(AmqpBind::class))
        ;

        $message = new AmqpMessage('the body', ['key' => 'value'], ['hkey' => 'hvalue']);
        $message->setRoutingKey('the-routing-key');

        $dest = new AmqpTopic('the-topic');
        $dest->setFlags(12345);

        $strategy = new RabbitMqDelayPluginDelayStrategy();
        $strategy->delayMessage($context, $dest, $message, 10000);

        $this->assertSame(12345, $delayedTopic->getFlags());
        $this->assertSame('x-delayed-message', $delayedTopic->getType());
        $this->assertSame([
            'x-delayed-type' => 'direct',
        ], $delayedTopic->getArguments());

        $this->assertSame(['x-delay' => 10000], $delayedMessage->getProperties());
        $this->assertSame('the-routing-key', $delayedMessage->getRoutingKey());
    }

    public function testShouldSendDelayedMessageToQueue()
    {
        $delayedTopic = new AmqpTopic('the-topic');
        $delayedMessage = new AmqpMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($delayedTopic), $this->identicalTo($delayedMessage))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createTopic')
            ->with($this->identicalTo('enqueue.queue.delayed'))
            ->willReturn($delayedTopic)
        ;
        $context
            ->expects($this->once())
            ->method('declareTopic')
            ->with($this->identicalTo($delayedTopic))
        ;
        $context
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producer)
        ;
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->with($this->identicalTo('the body'), $this->identicalTo(['key' => 'value']), $this->identicalTo(['hkey' => 'hvalue']))
            ->willReturn($delayedMessage)
        ;
        $context
            ->expects($this->once())
            ->method('bind')
            ->with($this->isInstanceOf(AmqpBind::class))
        ;

        $message = new AmqpMessage('the body', ['key' => 'value'], ['hkey' => 'hvalue']);
        $message->setRoutingKey('the-routing-key');

        $dest = new AmqpQueue('the-queue');

        $strategy = new RabbitMqDelayPluginDelayStrategy();
        $strategy->delayMessage($context, $dest, $message, 10000);

        $this->assertSame(AmqpQueue::FLAG_DURABLE, $delayedTopic->getFlags());
        $this->assertSame('x-delayed-message', $delayedTopic->getType());
        $this->assertSame([
            'x-delayed-type' => 'direct',
        ], $delayedTopic->getArguments());

        $this->assertSame(['x-delay' => 10000], $delayedMessage->getProperties());
        $this->assertSame('the-queue', $delayedMessage->getRoutingKey());
    }

    public function testShouldThrowExceptionIfInvalidDestination()
    {
        $delayedMessage = new AmqpMessage();

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createMessage')
            ->willReturn($delayedMessage)
        ;

        $strategy = new RabbitMqDelayPluginDelayStrategy();

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Interop\Amqp\AmqpTopic|Interop\Amqp\AmqpQueue but got');

        $strategy->delayMessage($context, $this->createMock(AmqpDestination::class), new AmqpMessage(), 10000);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpContext
     */
    private function createContextMock()
    {
        return $this->createMock(AmqpContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TestProducer
     */
    private function createProducerMock()
    {
        return $this->createMock(TestProducer::class);
    }
}

class TestProducer implements AmqpProducer, DelayStrategy
{
    public function delayMessage(AmqpContext $context, AmqpDestination $dest, \Interop\Amqp\AmqpMessage $message, $delayMsec)
    {
    }

    public function send(PsrDestination $destination, PsrMessage $message)
    {
    }

    public function setDeliveryDelay($deliveryDelay)
    {
    }

    public function getDeliveryDelay()
    {
    }

    public function setPriority($priority)
    {
    }

    public function getPriority()
    {
    }

    public function setTimeToLive($timeToLive)
    {
    }

    public function getTimeToLive()
    {
    }
}
