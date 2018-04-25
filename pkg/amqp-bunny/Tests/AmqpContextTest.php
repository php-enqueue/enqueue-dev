<?php

namespace Enqueue\AmqpBunny\Tests;

use Bunny\Channel;
use Bunny\Protocol\MethodQueueDeclareOkFrame;
use Enqueue\AmqpBunny\AmqpContext;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use PHPUnit\Framework\TestCase;

class AmqpContextTest extends TestCase
{
    public function testShouldDeclareTopic()
    {
        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('exchangeDeclare')
            ->with(
                $this->identicalTo('name'),
                $this->identicalTo('type'),
                $this->isTrue(),
                $this->isTrue(),
                $this->isTrue(),
                $this->isTrue(),
                $this->isTrue(),
                $this->identicalTo(['key' => 'value'])
            )
        ;

        $topic = new AmqpTopic('name');
        $topic->setType('type');
        $topic->setArguments(['key' => 'value']);
        $topic->addFlag(AmqpTopic::FLAG_DURABLE);
        $topic->addFlag(AmqpTopic::FLAG_NOWAIT);
        $topic->addFlag(AmqpTopic::FLAG_PASSIVE);
        $topic->addFlag(AmqpTopic::FLAG_INTERNAL);
        $topic->addFlag(AmqpTopic::FLAG_AUTODELETE);

        $session = new AmqpContext($channel);
        $session->declareTopic($topic);
    }

    public function testShouldDeleteTopic()
    {
        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('exchangeDelete')
            ->with(
                $this->identicalTo('name'),
                $this->isTrue(),
                $this->isTrue()
            )
        ;

        $topic = new AmqpTopic('name');
        $topic->setType('type');
        $topic->setArguments(['key' => 'value']);
        $topic->addFlag(AmqpTopic::FLAG_IFUNUSED);
        $topic->addFlag(AmqpTopic::FLAG_NOWAIT);

        $session = new AmqpContext($channel);
        $session->deleteTopic($topic);
    }

    public function testShouldDeclareQueue()
    {
        $frame = new MethodQueueDeclareOkFrame();
        $frame->queue = 'name';
        $frame->messageCount = 123;

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('queueDeclare')
            ->with(
                $this->identicalTo('name'),
                $this->isTrue(),
                $this->isTrue(),
                $this->isTrue(),
                $this->isTrue(),
                $this->isTrue(),
                $this->identicalTo(['key' => 'value'])
            )
            ->willReturn($frame)
        ;

        $queue = new AmqpQueue('name');
        $queue->setArguments(['key' => 'value']);
        $queue->addFlag(AmqpQueue::FLAG_AUTODELETE);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);
        $queue->addFlag(AmqpQueue::FLAG_NOWAIT);
        $queue->addFlag(AmqpQueue::FLAG_PASSIVE);
        $queue->addFlag(AmqpQueue::FLAG_EXCLUSIVE);
        $queue->addFlag(AmqpQueue::FLAG_NOWAIT);

        $session = new AmqpContext($channel);

        $this->assertSame(123, $session->declareQueue($queue));
    }

    public function testShouldDeleteQueue()
    {
        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('queueDelete')
            ->with(
                $this->identicalTo('name'),
                $this->isTrue(),
                $this->isTrue(),
                $this->isTrue()
            )
        ;

        $queue = new AmqpQueue('name');
        $queue->setArguments(['key' => 'value']);
        $queue->addFlag(AmqpQueue::FLAG_IFUNUSED);
        $queue->addFlag(AmqpQueue::FLAG_IFEMPTY);
        $queue->addFlag(AmqpQueue::FLAG_NOWAIT);

        $session = new AmqpContext($channel);
        $session->deleteQueue($queue);
    }

    public function testBindShouldBindTopicToTopic()
    {
        $source = new AmqpTopic('source');
        $target = new AmqpTopic('target');

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('exchangeBind')
            ->with($this->identicalTo('target'), $this->identicalTo('source'), $this->identicalTo('routing-key'), $this->isTrue())
        ;

        $context = new AmqpContext($channel);
        $context->bind(new AmqpBind($target, $source, 'routing-key', 12345));
    }

    public function testBindShouldBindTopicToQueue()
    {
        $source = new AmqpTopic('source');
        $target = new AmqpQueue('target');

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->exactly(2))
            ->method('queueBind')
            ->with($this->identicalTo('target'), $this->identicalTo('source'), $this->identicalTo('routing-key'), $this->isTrue())
        ;

        $context = new AmqpContext($channel);
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
            ->method('exchangeUnbind')
            ->with($this->identicalTo('target'), $this->identicalTo('source'), $this->identicalTo('routing-key'), $this->isTrue())
        ;

        $context = new AmqpContext($channel);
        $context->unbind(new AmqpBind($target, $source, 'routing-key', 12345));
    }

    public function testShouldUnBindTopicFromQueue()
    {
        $source = new AmqpTopic('source');
        $target = new AmqpQueue('target');

        $channel = $this->createChannelMock();
        $channel
            ->expects($this->exactly(2))
            ->method('queueUnbind')
            ->with($this->identicalTo('target'), $this->identicalTo('source'), $this->identicalTo('routing-key'), ['key' => 'value'])
        ;

        $context = new AmqpContext($channel);
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

        $context = new AmqpContext($channel);
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
            ->method('queuePurge')
            ->with($this->identicalTo('queue'), $this->isTrue())
        ;

        $context = new AmqpContext($channel);
        $context->purgeQueue($queue);
    }

    public function testShouldSetQos()
    {
        $channel = $this->createChannelMock();
        $channel
            ->expects($this->once())
            ->method('qos')
            ->with($this->identicalTo(123), $this->identicalTo(456), $this->isTrue())
        ;

        $context = new AmqpContext($channel);
        $context->setQos(123, 456, true);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Channel
     */
    public function createChannelMock()
    {
        return $this->createMock(Channel::class);
    }
}
