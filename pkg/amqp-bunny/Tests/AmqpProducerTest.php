<?php

namespace Enqueue\AmqpBunny\Tests;

use Bunny\Channel;
use Bunny\Message;
use Enqueue\AmqpBunny\AmqpProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Amqp\AmqpMessage as InteropAmqpMessage;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use PHPUnit\Framework\TestCase;

class AmqpProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new AmqpProducer($this->createBunnyChannelMock());
    }

    public function testShouldImplementPsrProducerInterface()
    {
        $this->assertClassImplements(PsrProducer::class, AmqpProducer::class);
    }

    public function testShouldThrowExceptionWhenDestinationTypeIsInvalid()
    {
        $producer = new AmqpProducer($this->createBunnyChannelMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Interop\Amqp\AmqpQueue but got');

        $producer->send($this->createDestinationMock(), new AmqpMessage());
    }

    public function testShouldThrowExceptionWhenMessageTypeIsInvalid()
    {
        $producer = new AmqpProducer($this->createBunnyChannelMock());

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

        $producer = new AmqpProducer($channel);
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

        $producer = new AmqpProducer($channel);
        $producer->send($queue, new AmqpMessage('body'));
    }

    public function testShouldSetMessageHeaders()
    {
        $channel = $this->createBunnyChannelMock();
        $channel
            ->expects($this->once())
            ->method('publish')
            ->with($this->anything(), ['content_type' => 'text/plain'])
        ;

        $producer = new AmqpProducer($channel);
        $producer->send(new AmqpTopic('name'), new AmqpMessage('body', [], ['content_type' => 'text/plain']));
    }

    public function testShouldSetMessageProperties()
    {
        $channel = $this->createBunnyChannelMock();
        $channel
            ->expects($this->once())
            ->method('publish')
            ->with($this->anything(), ['application_headers' => ['key' => 'value']])
        ;

        $producer = new AmqpProducer($channel);
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

        $producer = new AmqpProducer($channel);
        $producer->send(new AmqpTopic('name'), $message);
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
     * @return \PHPUnit_Framework_MockObject_MockObject|Channel
     */
    private function createBunnyChannelMock()
    {
        return $this->createMock(Channel::class);
    }
}
