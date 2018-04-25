<?php

namespace Enqueue\AmqpTools\Tests;

use Enqueue\AmqpTools\DelayStrategy;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpProducer;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use Interop\Queue\InvalidDestinationException;
use PHPUnit\Framework\TestCase;

class RabbitMqDlxDelayStrategyTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementDelayStrategyInterface()
    {
        $this->assertClassImplements(DelayStrategy::class, RabbitMqDlxDelayStrategy::class);
    }

    public function testShouldSendDelayedMessageToTopic()
    {
        $delayedQueue = new AmqpQueue('the-queue');
        $delayedMessage = new AmqpMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($delayedQueue), $this->identicalTo($delayedMessage))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with($this->identicalTo('enqueue.the-topic.the-routing-key.10000.x.delay'))
            ->willReturn($delayedQueue)
        ;
        $context
            ->expects($this->once())
            ->method('declareQueue')
            ->with($this->identicalTo($delayedQueue))
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

        $message = new AmqpMessage('the body', ['key' => 'value'], ['hkey' => 'hvalue']);
        $message->setRoutingKey('the-routing-key');

        $dest = new AmqpTopic('the-topic');

        $strategy = new RabbitMqDlxDelayStrategy();
        $strategy->delayMessage($context, $dest, $message, 10000);

        $this->assertSame(AmqpQueue::FLAG_DURABLE, $delayedQueue->getFlags());
        $this->assertSame([
            'x-message-ttl' => 10000,
            'x-dead-letter-exchange' => 'the-topic',
            'x-dead-letter-routing-key' => 'the-routing-key',
        ], $delayedQueue->getArguments());
    }

    public function testShouldSendDelayedMessageToQueue()
    {
        $delayedQueue = new AmqpQueue('the-queue');
        $delayedMessage = new AmqpMessage();

        $producer = $this->createProducerMock();
        $producer
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($delayedQueue), $this->identicalTo($delayedMessage))
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with($this->identicalTo('enqueue.the-queue.10000.delayed'))
            ->willReturn($delayedQueue)
        ;
        $context
            ->expects($this->once())
            ->method('declareQueue')
            ->with($this->identicalTo($delayedQueue))
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

        $message = new AmqpMessage('the body', ['key' => 'value'], ['hkey' => 'hvalue']);
        $message->setRoutingKey('the-routing-key');

        $dest = new AmqpQueue('the-queue');

        $strategy = new RabbitMqDlxDelayStrategy();
        $strategy->delayMessage($context, $dest, $message, 10000);

        $this->assertSame(AmqpQueue::FLAG_DURABLE, $delayedQueue->getFlags());
        $this->assertSame([
            'x-message-ttl' => 10000,
            'x-dead-letter-exchange' => '',
            'x-dead-letter-routing-key' => 'the-queue',
        ], $delayedQueue->getArguments());
    }

    public function testShouldUnsetXDeathProperty()
    {
        $delayedQueue = new AmqpQueue('the-queue');
        $delayedMessage = new AmqpMessage();

        $producer = $this->createProducerMock();

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('createQueue')
            ->with($this->identicalTo('enqueue.the-queue.10000.delayed'))
            ->willReturn($delayedQueue)
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

        $message = new AmqpMessage('the body', ['key' => 'value', 'x-death' => 'value'], ['hkey' => 'hvalue']);

        $dest = new AmqpQueue('the-queue');

        $strategy = new RabbitMqDlxDelayStrategy();
        $strategy->delayMessage($context, $dest, $message, 10000);
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

        $strategy = new RabbitMqDlxDelayStrategy();

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
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpProducer
     */
    private function createProducerMock()
    {
        return $this->createMock(AmqpProducer::class);
    }
}
