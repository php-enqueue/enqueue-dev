<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Null\NullQueue;
use Enqueue\Null\NullTopic;
use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\PsrContext;
use Enqueue\Redis\Redis;
use Enqueue\Redis\RedisConsumer;
use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisDestination;
use Enqueue\Redis\RedisMessage;
use Enqueue\Redis\RedisProducer;
use Enqueue\Test\ClassExtensionTrait;

class RedisContextTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementContextInterface()
    {
        $this->assertClassImplements(PsrContext::class, RedisContext::class);
    }

    public function testCouldBeConstructedWithRedisAsFirstArgument()
    {
        new RedisContext($this->createRedisMock());
    }

    public function testCouldBeConstructedWithRedisFactoryAsFirstArgument()
    {
        new RedisContext(function () {
            return $this->createRedisMock();
        });
    }

    public function testThrowIfNeitherRedisNorFactoryGiven()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The $redis argument must be either Enqueue\Redis\Redis or callable that returns $s once called.');
        new RedisContext(new \stdClass());
    }

    public function testShouldAllowCreateEmptyMessage()
    {
        $context = new RedisContext($this->createRedisMock());

        $message = $context->createMessage();

        $this->assertInstanceOf(RedisMessage::class, $message);

        $this->assertSame('', $message->getBody());
        $this->assertSame([], $message->getProperties());
        $this->assertSame([], $message->getHeaders());
    }

    public function testShouldAllowCreateCustomMessage()
    {
        $context = new RedisContext($this->createRedisMock());

        $message = $context->createMessage('theBody', ['aProp' => 'aPropVal'], ['aHeader' => 'aHeaderVal']);

        $this->assertInstanceOf(RedisMessage::class, $message);

        $this->assertSame('theBody', $message->getBody());
        $this->assertSame(['aProp' => 'aPropVal'], $message->getProperties());
        $this->assertSame(['aHeader' => 'aHeaderVal'], $message->getHeaders());
    }

    public function testShouldCreateQueue()
    {
        $context = new RedisContext($this->createRedisMock());

        $queue = $context->createQueue('aQueue');

        $this->assertInstanceOf(RedisDestination::class, $queue);
        $this->assertSame('aQueue', $queue->getQueueName());
    }

    public function testShouldAllowCreateTopic()
    {
        $context = new RedisContext($this->createRedisMock());

        $topic = $context->createTopic('aTopic');

        $this->assertInstanceOf(RedisDestination::class, $topic);
        $this->assertSame('aTopic', $topic->getTopicName());
    }

    public function testThrowNotImplementedOnCreateTmpQueueCall()
    {
        $context = new RedisContext($this->createRedisMock());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Not implemented');
        $context->createTemporaryQueue();
    }

    public function testShouldCreateProducer()
    {
        $context = new RedisContext($this->createRedisMock());

        $producer = $context->createProducer();

        $this->assertInstanceOf(RedisProducer::class, $producer);
    }

    public function testShouldThrowIfNotRedisDestinationGivenOnCreateConsumer()
    {
        $context = new RedisContext($this->createRedisMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\Redis\RedisDestination but got Enqueue\Null\NullQueue.');
        $consumer = $context->createConsumer(new NullQueue('aQueue'));

        $this->assertInstanceOf(RedisConsumer::class, $consumer);
    }

    public function testShouldCreateConsumer()
    {
        $context = new RedisContext($this->createRedisMock());

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

        $context = new RedisContext($redisMock);

        $context->close();
    }

    public function testThrowIfNotRedisDestinationGivenOnDeleteQueue()
    {
        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->never())
            ->method('del')
        ;

        $context = new RedisContext($redisMock);

        $this->expectException(InvalidDestinationException::class);
        $context->deleteQueue(new NullQueue('aQueue'));
    }

    public function testShouldAllowDeleteQueue()
    {
        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('del')
            ->with('aQueueName')
        ;

        $context = new RedisContext($redisMock);

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

        $context = new RedisContext($redisMock);

        $this->expectException(InvalidDestinationException::class);
        $context->deleteTopic(new NullTopic('aTopic'));
    }

    public function testShouldAllowDeleteTopic()
    {
        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('del')
            ->with('aTopicName')
        ;

        $context = new RedisContext($redisMock);

        $topic = $context->createTopic('aTopicName');

        $context->deleteQueue($topic);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Redis
     */
    private function createRedisMock()
    {
        return $this->createMock(Redis::class);
    }
}
