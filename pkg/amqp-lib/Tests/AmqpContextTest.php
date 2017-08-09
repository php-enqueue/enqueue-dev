<?php

namespace Enqueue\AmqpLib\Tests;

use Enqueue\AmqpLib\AmqpContext;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Wire\AMQPTable;
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
                $this->isInstanceOf(AMQPTable::class),
                $this->isNull()
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
        $topic->addFlag(AmqpTopic::FLAG_DURABLE);
        $topic->addFlag(AmqpTopic::FLAG_NOWAIT);
        $topic->addFlag(AmqpTopic::FLAG_PASSIVE);
        $topic->addFlag(AmqpTopic::FLAG_INTERNAL);
        $topic->addFlag(AmqpTopic::FLAG_AUTODELETE);

        $session = new AmqpContext($connection);
        $session->declareTopic($topic);
    }

    public function testShouldDeleteTopic()
    {
        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('exchange_delete')
            ->with(
                $this->identicalTo('name'),
                $this->isTrue(),
                $this->isTrue()
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
        $topic->addFlag(AmqpTopic::FLAG_IFUNUSED);
        $topic->addFlag(AmqpTopic::FLAG_NOWAIT);

        $session = new AmqpContext($connection);
        $session->deleteTopic($topic);
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
                $this->isInstanceOf(AMQPTable::class),
                $this->isNull()
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
        $queue->addFlag(AmqpQueue::FLAG_AUTODELETE);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);
        $queue->addFlag(AmqpQueue::FLAG_NOWAIT);
        $queue->addFlag(AmqpQueue::FLAG_PASSIVE);
        $queue->addFlag(AmqpQueue::FLAG_EXCLUSIVE);
        $queue->addFlag(AmqpQueue::FLAG_NOWAIT);

        $session = new AmqpContext($connection);
        $session->declareQueue($queue);
    }

    public function testShouldDeleteQueue()
    {
        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('queue_delete')
            ->with(
                $this->identicalTo('name'),
                $this->isTrue(),
                $this->isTrue(),
                $this->isTrue()
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
        $queue->addFlag(AmqpQueue::FLAG_IFUNUSED);
        $queue->addFlag(AmqpQueue::FLAG_IFEMPTY);
        $queue->addFlag(AmqpQueue::FLAG_NOWAIT);

        $session = new AmqpContext($connection);
        $session->deleteQueue($queue);
    }

    public function testBindShouldBindTopicToTopic()
    {
        $source = new AmqpTopic('source');
        $target = new AmqpTopic('target');

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('exchange_bind')
            ->with($this->identicalTo('target'), $this->identicalTo('source'), $this->identicalTo('routing-key'), $this->isTrue())
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel)
        ;

        $context = new AmqpContext($connection);
        $context->bind(new AmqpBind($target, $source, 'routing-key', 12345));
    }

    public function testBindShouldBindTopicToQueue()
    {
        $source = new AmqpTopic('source');
        $target = new AmqpQueue('target');

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->exactly(2))
            ->method('queue_bind')
            ->with($this->identicalTo('target'), $this->identicalTo('source'), $this->identicalTo('routing-key'), $this->isTrue())
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel)
        ;

        $context = new AmqpContext($connection);
        $context->bind(new AmqpBind($target, $source, 'routing-key', 12345));
        $context->bind(new AmqpBind($source, $target, 'routing-key', 12345));
    }

    public function testShouldUnBindTopicFromTopic()
    {
        $source = new AmqpTopic('source');
        $target = new AmqpTopic('target');

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('exchange_unbind')
            ->with($this->identicalTo('target'), $this->identicalTo('source'), $this->identicalTo('routing-key'), $this->isTrue())
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel)
        ;

        $context = new AmqpContext($connection);
        $context->unbind(new AmqpBind($target, $source, 'routing-key', 12345));
    }

    public function testShouldUnBindTopicFromQueue()
    {
        $source = new AmqpTopic('source');
        $target = new AmqpQueue('target');

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->exactly(2))
            ->method('queue_unbind')
            ->with($this->identicalTo('target'), $this->identicalTo('source'), $this->identicalTo('routing-key'), ['key' => 'value'])
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel)
        ;

        $context = new AmqpContext($connection);
        $context->unbind(new AmqpBind($target, $source, 'routing-key', 12345, ['key' => 'value']));
        $context->unbind(new AmqpBind($source, $target, 'routing-key', 12345, ['key' => 'value']));
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

        $context = new AmqpContext($connection);
        $context->createProducer();

        $context->close();
    }

    public function testShouldPurgeQueue()
    {
        $queue = new AmqpQueue('queue');
        $queue->addFlag(AmqpQueue::FLAG_NOWAIT);

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('queue_purge')
            ->with($this->identicalTo('queue'), $this->isTrue())
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel)
        ;

        $context = new AmqpContext($connection);
        $context->purgeQueue($queue);
    }

    public function testShouldSetQos()
    {
        $channel = $this->createChannelMock();
        $channel
            ->expects($this->at(0))
            ->method('basic_qos')
            ->with($this->identicalTo(0), $this->identicalTo(1), $this->isFalse())
        ;
        $channel
            ->expects($this->at(1))
            ->method('basic_qos')
            ->with($this->identicalTo(123), $this->identicalTo(456), $this->isTrue())
        ;

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('channel')
            ->willReturn($channel)
        ;

        $context = new AmqpContext($connection);
        $context->setQos(123, 456, true);
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
