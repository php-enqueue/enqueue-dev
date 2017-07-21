<?php

namespace Enqueue\AmqpLib\Tests;

use Enqueue\AmqpLib\AmqpContext;
use Enqueue\AmqpLib\AmqpQueue;
use Enqueue\AmqpLib\AmqpTopic;
use Enqueue\Null\NullQueue;
use Enqueue\Null\NullTopic;
use Interop\Queue\Exception;
use Interop\Queue\InvalidDestinationException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PHPUnit\Framework\TestCase;

class AmqpContextTest extends TestCase
{
    public function testShouldDeclareTopic()
    {
        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('exchange_declare')
            ->with(
                $this->identicalTo('name'),
                $this->identicalTo('type'),
                $this->isTrue(),
                $this->isTrue(),
                $this->isTrue(),
                $this->isTrue(),
                $this->isTrue(),
                $this->identicalTo(['key' => 'value']),
                $this->identicalTo(12345)
            )
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel)
        ;

        $topic = new AmqpTopic('name');
        $topic->setType('type');
        $topic->setArguments(['key' => 'value']);
        $topic->setAutoDelete(true);
        $topic->setDurable(true);
        $topic->setInternal(true);
        $topic->setNoWait(true);
        $topic->setPassive(true);
        $topic->setRoutingKey('routing-key');
        $topic->setTicket(12345);

        $session = new AmqpContext($connection, '');
        $session->declareTopic($topic);
    }

    public function testShouldDeclareQueue()
    {
        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('queue_declare')
            ->with(
                $this->identicalTo('name'),
                $this->isTrue(),
                $this->isTrue(),
                $this->isTrue(),
                $this->isTrue(),
                $this->isTrue(),
                $this->identicalTo(['key' => 'value']),
                $this->identicalTo(12345)
            )
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel)
        ;

        $queue = new AmqpQueue('name');
        $queue->setArguments(['key' => 'value']);
        $queue->setAutoDelete(true);
        $queue->setDurable(true);
        $queue->setNoWait(true);
        $queue->setPassive(true);
        $queue->setTicket(12345);
        $queue->setConsumerTag('consumer-tag');
        $queue->setExclusive(true);
        $queue->setNoLocal(true);

        $session = new AmqpContext($connection, '');
        $session->declareQueue($queue);
    }

    public function testDeclareBindShouldThrowExceptionIfSourceDestinationIsInvalid()
    {
        $context = new AmqpContext($this->createConnectionMock(), '');

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\AmqpLib\AmqpTopic but got');

        $context->bind(new NullTopic(''), new AmqpTopic('name'));
    }

    public function testDeclareBindShouldThrowExceptionIfTargetDestinationIsInvalid()
    {
        $context = new AmqpContext($this->createConnectionMock(), '');

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\AmqpLib\AmqpTopic but got');

        $context->bind(new AmqpQueue('name'), new NullTopic(''));
    }

    public function testDeclareBindShouldThrowExceptionWhenSourceAndTargetAreQueues()
    {
        $context = new AmqpContext($this->createConnectionMock(), '');

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Is not possible to bind queue to queue. It is possible to bind topic to queue or topic to topic');

        $context->bind(new AmqpQueue('name'), new AmqpQueue('name'));
    }

    public function testDeclareBindShouldBindTopicToTopic()
    {
        $source = new AmqpTopic('source');
        $target = new AmqpTopic('target');

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('exchange_bind')
            ->with('target', 'source')
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel)
        ;

        $context = new AmqpContext($connection, '');
        $context->bind($source, $target);
    }

    public function testDeclareBindShouldBindTopicToQueue()
    {
        $source = new AmqpTopic('source');
        $target = new AmqpQueue('target');

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->exactly(2))
            ->method('queue_bind')
            ->with('target', 'source')
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel)
        ;

        $context = new AmqpContext($connection, '');
        $context->bind($source, $target);
        $context->bind($target, $source);
    }

    public function testShouldCloseChannelConnection()
    {
        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('close')
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel)
        ;

        $context = new AmqpContext($connection, '');
        $context->createProducer();

        $context->close();
    }

    public function testPurgeShouldThrowExceptionIfDestinationIsNotAmqpQueue()
    {
        $context = new AmqpContext($this->createConnectionMock(), '');

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\AmqpLib\AmqpQueue but got');

        $context->purge(new NullQueue(''));
    }

    public function testShouldPurgeQueue()
    {
        $queue = new AmqpQueue('queue');

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('queue_purge')
            ->with('queue')
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel)
        ;

        $context = new AmqpContext($connection, '');
        $context->purge($queue);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractConnection
     */
    public function createConnectionMock()
    {
        return $this->createMock(AbstractConnection::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AMQPChannel
     */
    public function createChannelMock()
    {
        return $this->createMock(AMQPChannel::class);
    }
}
