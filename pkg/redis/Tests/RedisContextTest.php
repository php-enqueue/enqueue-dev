<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Null\NullQueue;
use Enqueue\Null\NullTopic;
use Enqueue\Redis\Redis;
use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Redis\RedisConsumer;
use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisDestination;
use Enqueue\Redis\RedisMessage;
use Enqueue\Redis\RedisProducer;
use Enqueue\Redis\RedisSubscriptionConsumer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;

class RedisContextTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementContextInterface()
    {
        $this->assertClassImplements(Context::class, RedisContext::class);
    }

    public function testCouldBeConstructedWithRedisAsFirstArgument()
    {
        new RedisContext($this->createRedisMock(), []);
    }

    public function testCouldBeConstructedWithRedisFactoryAsFirstArgument()
    {
        new RedisContext(function () {
            return $this->createRedisMock();
        }, []);
    }

    public function testThrowIfNeitherRedisNorFactoryGiven()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The $redis argument must be either Enqueue\Redis\Redis or callable that returns Enqueue\Redis\Redis once called.');
        new RedisContext(new \stdClass(), []);
    }

    public function testShouldAllowCreateEmptyMessage()
    {
        $context = new RedisContext($this->createRedisMock(), []);

        $message = $context->createMessage();

        $this->assertInstanceOf(RedisMessage::class, $message);

        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getProperties());
        $this->assertSame([], $message->getHeaders());
    }

    public function testShouldAllowCreateCustomMessage()
    {
        $context = new RedisContext($this->createRedisMock(), []);

        $message = $context->createMessage('theBody', ['aProp' => 'aPropVal'], ['aHeader' => 'aHeaderVal']);

        $this->assertInstanceOf(RedisMessage::class, $message);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['aProp' => 'aPropVal'], $message->getProperties());
        $this->assertSame(['aHeader' => 'aHeaderVal'], $message->getHeaders());
    }

    public function testShouldCreateQueue()
    {
        $context = new RedisContext($this->createRedisMock(), []);

        $queue = $context->createQueue('aQueue');

        $this->assertInstanceOf(RedisDestination::class, $queue);
        $this->assertSame('aQueue', $queue->getQueueName());
    }

    public function testShouldAllowCreateTopic()
    {
        $context = new RedisContext($this->createRedisMock(), []);

        $topic = $context->createTopic('aTopic');

        $this->assertInstanceOf(RedisDestination::class, $topic);
        $this->assertSame('aTopic', $topic->getTopicName());
    }

    public function testThrowNotImplementedOnCreateTmpQueueCall()
    {
        $context = new RedisContext($this->createRedisMock(), []);

        $this->expectException(TemporaryQueueNotSupportedException::class);

        $context->createTemporaryQueue();
    }

    public function testShouldCreateProducer()
    {
        $context = new RedisContext($this->createRedisMock(), []);

        $producer = $context->createProducer();

        $this->assertInstanceOf(RedisProducer::class, $producer);
    }

    public function testShouldThrowIfNotRedisDestinationGivenOnCreateConsumer()
    {
        $context = new RedisContext($this->createRedisMock(), []);

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\Redis\RedisDestination but got Enqueue\Null\NullQueue.');
        $consumer = $context->createConsumer(new NullQueue('aQueue'));

        $this->assertInstanceOf(RedisConsumer::class, $consumer);
    }

    public function testShouldCreateConsumer()
    {
        $context = new RedisContext($this->createRedisMock(), [
            'consume_strategy' => RedisConnectionFactory::CONSUME_STRATEGY_BLOCKING,
            'redelivery_delay' => 12345,
        ]);

        $queue = $context->createQueue('aQueue');

        $consumer = $context->createConsumer($queue);

        $this->assertInstanceOf(RedisConsumer::class, $consumer);
    }

    public function testShouldCallRedisDisconnectOnClose()
    {
        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('disconnect')
        ;

        $context = new RedisContext($redisMock, []);

        $context->close();
    }

    public function testThrowIfNotRedisDestinationGivenOnDeleteQueue()
    {
        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->never())
            ->method('del')
        ;

        $context = new RedisContext($redisMock, []);

        $this->expectException(InvalidDestinationException::class);
        $context->deleteQueue(new NullQueue('aQueue'));
    }

    public function testShouldAllowDeleteQueue()
    {
        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->at(0))
            ->method('del')
            ->with('aQueueName')
        ;
        $redisMock
            ->expects($this->at(1))
            ->method('del')
            ->with('aQueueName:delayed')
        ;
        $redisMock
            ->expects($this->at(2))
            ->method('del')
            ->with('aQueueName:reserved')
        ;

        $context = new RedisContext($redisMock, []);

        $queue = $context->createQueue('aQueueName');

        $context->deleteQueue($queue);
    }

    public function testThrowIfNotRedisDestinationGivenOnDeleteTopic()
    {
        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->never())
            ->method('del')
        ;

        $context = new RedisContext($redisMock, []);

        $this->expectException(InvalidDestinationException::class);
        $context->deleteTopic(new NullTopic('aTopic'));
    }

    public function testShouldAllowDeleteTopic()
    {
        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->at(0))
            ->method('del')
            ->with('aTopicName')
        ;
        $redisMock
            ->expects($this->at(1))
            ->method('del')
            ->with('aTopicName:delayed')
        ;
        $redisMock
            ->expects($this->at(2))
            ->method('del')
            ->with('aTopicName:reserved')
        ;

        $context = new RedisContext($redisMock, []);

        $topic = $context->createTopic('aTopicName');

        $context->deleteQueue($topic);
    }

    public function testShouldReturnExpectedSubscriptionConsumerInstance()
    {
        $context = new RedisContext($this->createRedisMock(), [
            'consume_strategy' => RedisConnectionFactory::CONSUME_STRATEGY_BLOCKING,
            'redelivery_delay' => 12345,
        ]);

        $this->assertInstanceOf(RedisSubscriptionConsumer::class, $context->createSubscriptionConsumer());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Redis
     */
    private function createRedisMock()
    {
        return $this->createMock(Redis::class);
    }
}
