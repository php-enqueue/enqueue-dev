<?php

namespace Enqueue\AmqpLib\Tests;

use Enqueue\AmqpLib\AmqpContext;
use Enqueue\AmqpLib\AmqpProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage as LibAMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use PHPUnit\Framework\TestCase;

class AmqpProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AmqpProducer($this->createAmqpChannelMock(), $this->createContextMock());
    }

    public function testShouldImplementPsrProducerInterface()
    {
        $this->assertClassImplements(PsrProducer::class, AmqpProducer::class);
    }

    public function testShouldThrowExceptionWhenDestinationTypeIsInvalid()
    {
        $producer = new AmqpProducer($this->createAmqpChannelMock(), $this->createContextMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Interop\Amqp\AmqpQueue but got');

        $producer->send($this->createDestinationMock(), new AmqpMessage());
    }

    public function testShouldThrowExceptionWhenMessageTypeIsInvalid()
    {
        $producer = new AmqpProducer($this->createAmqpChannelMock(), $this->createContextMock());

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Interop\Amqp\AmqpMessage but it is');

        $producer->send(new AmqpTopic('name'), $this->createMessageMock());
    }

    public function testShouldPublishMessageToTopic()
    {
        $amqpMessage = null;

        $channel = $this->createAmqpChannelMock();
        $channel
            ->expects($this->once())
            ->method('basic_publish')
            ->with($this->isInstanceOf(LibAMQPMessage::class), 'topic', 'routing-key')
            ->will($this->returnCallback(function (LibAMQPMessage $message) use (&$amqpMessage) {
                $amqpMessage = $message;
            }))
        ;

        $topic = new AmqpTopic('topic');

        $message = new AmqpMessage('body');
        $message->setRoutingKey('routing-key');

        $producer = new AmqpProducer($channel, $this->createContextMock());
        $producer->send($topic, $message);

        $this->assertEquals('body', $amqpMessage->getBody());
    }

    public function testShouldPublishMessageToQueue()
    {
        $amqpMessage = null;

        $channel = $this->createAmqpChannelMock();
        $channel
            ->expects($this->once())
            ->method('basic_publish')
            ->with($this->isInstanceOf(LibAMQPMessage::class), $this->isEmpty(), 'queue')
            ->will($this->returnCallback(function (LibAMQPMessage $message) use (&$amqpMessage) {
                $amqpMessage = $message;
            }))
        ;

        $queue = new AmqpQueue('queue');

        $producer = new AmqpProducer($channel, $this->createContextMock());
        $producer->send($queue, new AmqpMessage('body'));

        $this->assertEquals('body', $amqpMessage->getBody());
    }

    public function testShouldSetMessageHeaders()
    {
        $amqpMessage = null;

        $channel = $this->createAmqpChannelMock();
        $channel
            ->expects($this->once())
            ->method('basic_publish')
            ->will($this->returnCallback(function (LibAMQPMessage $message) use (&$amqpMessage) {
                $amqpMessage = $message;
            }))
        ;

        $producer = new AmqpProducer($channel, $this->createContextMock());
        $producer->send(new AmqpTopic('name'), new AmqpMessage('body', [], ['content_type' => 'text/plain']));

        $this->assertEquals(['content_type' => 'text/plain'], $amqpMessage->get_properties());
    }

    public function testShouldSetMessageProperties()
    {
        $amqpMessage = null;

        $channel = $this->createAmqpChannelMock();
        $channel
            ->expects($this->once())
            ->method('basic_publish')
            ->will($this->returnCallback(function (LibAMQPMessage $message) use (&$amqpMessage) {
                $amqpMessage = $message;
            }))
        ;

        $producer = new AmqpProducer($channel, $this->createContextMock());
        $producer->send(new AmqpTopic('name'), new AmqpMessage('body', ['key' => 'value']));

        $properties = $amqpMessage->get_properties();

        $this->assertArrayHasKey('application_headers', $properties);
        $this->assertInstanceOf(AMQPTable::class, $properties['application_headers']);
        $this->assertEquals(['key' => 'value'], $properties['application_headers']->getNativeData());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrMessage
     */
    private function createMessageMock()
    {
        return $this->createMock(PsrMessage::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|PsrDestination
     */
    private function createDestinationMock()
    {
        return $this->createMock(PsrDestination::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AMQPChannel
     */
    private function createAmqpChannelMock()
    {
        return $this->createMock(AMQPChannel::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AmqpContext
     */
    private function createContextMock()
    {
        return $this->createMock(AmqpContext::class);
    }
}
