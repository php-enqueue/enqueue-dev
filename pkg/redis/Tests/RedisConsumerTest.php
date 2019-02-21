<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Redis\JsonSerializer;
use Enqueue\Redis\Redis;
use Enqueue\Redis\RedisConsumer;
use Enqueue\Redis\RedisConsumeStrategy;
use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisDestination;
use Enqueue\Redis\RedisMessage;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Consumer;

class RedisConsumerTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(Consumer::class, RedisConsumer::class);
    }

    public function testCouldBeConstructedWithContextAndDestinationAndPreFetchCountAsArguments()
    {
        new RedisConsumer($this->createContextMock(), new RedisDestination('aQueue'), $this->createMock(RedisConsumeStrategy::class));
    }

    public function testShouldReturnDestinationSetInConstructorOnGetQueue()
    {
        $destination = new RedisDestination('aQueue');

        $consumer = new RedisConsumer($this->createContextMock(), $destination, $this->createMock(RedisConsumeStrategy::class));

        $this->assertSame($destination, $consumer->getQueue());
    }

    public function testShouldAcknowledgeMessage()
    {
        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('zrem')
            ->with('aQueue:reserved', 'reserved-key')
            ->willReturn(1)
        ;

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->once())
            ->method('getRedis')
            ->willReturn($redisMock)
        ;

        $message = new RedisMessage();
        $message->setReservedKey('reserved-key');

        $consumer = new RedisConsumer($contextMock, new RedisDestination('aQueue'), $this->createMock(RedisConsumeStrategy::class));

        $consumer->acknowledge($message);
    }

    public function testShouldRejectMessage()
    {
        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('zrem')
            ->with('aQueue:reserved', 'reserved-key')
            ->willReturn(1)
        ;

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->once())
            ->method('getRedis')
            ->willReturn($redisMock)
        ;

        $message = new RedisMessage();
        $message->setReservedKey('reserved-key');

        $consumer = new RedisConsumer($contextMock, new RedisDestination('aQueue'), $this->createMock(RedisConsumeStrategy::class));

        $consumer->reject($message);
    }

    public function testShouldSendSameMessageToDestinationOnReQueue()
    {
        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('lpush')
            ->with('aQueue', '{"body":"text","properties":[],"headers":{"attempts":0}}')
            ->willReturn(1)
        ;

        $serializer = new JsonSerializer();

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->any())
            ->method('getRedis')
            ->willReturn($redisMock)
        ;
        $contextMock
            ->expects($this->any())
            ->method('getSerializer')
            ->willReturn($serializer)
        ;

        $message = new RedisMessage();
        $message->setBody('text');
        $message->setReservedKey($serializer->toString($message));

        $consumer = new RedisConsumer($contextMock, new RedisDestination('aQueue'), $this->createMock(RedisConsumeStrategy::class));

        $consumer->reject($message, true);
    }

    public function testShouldReceiveMessage()
    {
        $destination = new RedisDestination('aQueue');

        $contextMock = $this->createContextMock();

        $strategy = $this->createMock(RedisConsumeStrategy::class);
        $strategy
            ->expects($this->once())
            ->method('receiveMessage')
            ->willReturn($message = new RedisMessage())
        ;

        $consumer = new RedisConsumer($contextMock, $destination, $strategy);

        $result = $consumer->receive(2000);

        $this->assertNotNull($result);
        $this->assertSame($message, $result);
    }

    public function testShouldReceiveNoWaitMessage()
    {
        $destination = new RedisDestination('aQueue');

        $contextMock = $this->createContextMock();

        $strategy = $this->createMock(RedisConsumeStrategy::class);
        $strategy
            ->expects($this->once())
            ->method('receiveMessageNoWait')
            ->willReturn($message = new RedisMessage())
        ;

        $consumer = new RedisConsumer($contextMock, $destination, $strategy);

        $result = $consumer->receiveNoWait();

        $this->assertNotNull($result);
        $this->assertSame($message, $result);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Redis
     */
    private function createRedisMock()
    {
        return $this->createMock(Redis::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RedisContext
     */
    private function createContextMock()
    {
        return $this->createMock(RedisContext::class);
    }
}
