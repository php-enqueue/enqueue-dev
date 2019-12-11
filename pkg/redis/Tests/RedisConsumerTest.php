<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Redis\JsonSerializer;
use Enqueue\Redis\Redis;
use Enqueue\Redis\RedisConsumer;
use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisDestination;
use Enqueue\Redis\RedisMessage;
use Enqueue\Redis\RedisProducer;
use Enqueue\Redis\RedisResult;
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
        new RedisConsumer($this->createContextMock(), new RedisDestination('aQueue'));
    }

    public function testShouldReturnDestinationSetInConstructorOnGetQueue()
    {
        $destination = new RedisDestination('aQueue');

        $consumer = new RedisConsumer($this->createContextMock(), $destination);

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

        $consumer = new RedisConsumer($contextMock, new RedisDestination('aQueue'));

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

        $consumer = new RedisConsumer($contextMock, new RedisDestination('aQueue'));

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

        $consumer = new RedisConsumer($contextMock, new RedisDestination('aQueue'));

        $consumer->reject($message, true);
    }

    public function testShouldCallRedisBRPopAndReturnNullIfNothingInQueueOnReceive()
    {
        $destination = new RedisDestination('aQueue');

        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('brpop')
            ->with(['aQueue'], 2)
            ->willReturn(null)
        ;

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->any())
            ->method('getRedis')
            ->willReturn($redisMock)
        ;

        $consumer = new RedisConsumer($contextMock, $destination);

        $this->assertNull($consumer->receive(2000));
    }

    public function testShouldCallRedisBRPopAndReturnMessageIfOneInQueueOnReceive()
    {
        $destination = new RedisDestination('aQueue');

        $serializer = new JsonSerializer();

        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('brpop')
            ->with(['aQueue'], 2)
            ->willReturn(new RedisResult('aQueue', $serializer->toString(new RedisMessage('aBody'))))
        ;

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

        $consumer = new RedisConsumer($contextMock, $destination);

        $message = $consumer->receive(2000);

        $this->assertInstanceOf(RedisMessage::class, $message);
        $this->assertSame('aBody', $message->getBody());
    }

    public function testShouldCallRedisBRPopSeveralTimesWithFiveSecondTimeoutIfZeroTimeoutIsPassed()
    {
        $destination = new RedisDestination('aQueue');

        $expectedTimeout = 5;

        $serializer = new JsonSerializer();

        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->at(2))
            ->method('brpop')
            ->with(['aQueue'], $expectedTimeout)
            ->willReturn(null)
        ;
        $redisMock
            ->expects($this->at(5))
            ->method('brpop')
            ->with(['aQueue'], $expectedTimeout)
            ->willReturn(null)
        ;
        $redisMock
            ->expects($this->at(8))
            ->method('brpop')
            ->with(['aQueue'], $expectedTimeout)
            ->willReturn(new RedisResult('aQueue', $serializer->toString(new RedisMessage('aBody'))))
        ;

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->atLeastOnce())
            ->method('getRedis')
            ->willReturn($redisMock)
        ;
        $contextMock
            ->expects($this->atLeastOnce())
            ->method('getSerializer')
            ->willReturn($serializer)
        ;

        $consumer = new RedisConsumer($contextMock, $destination);

        $message = $consumer->receive(0);

        $this->assertInstanceOf(RedisMessage::class, $message);
        $this->assertSame('aBody', $message->getBody());
    }

    public function testShouldCallRedisRPopAndReturnNullIfNothingInQueueOnReceiveNoWait()
    {
        $destination = new RedisDestination('aQueue');

        $serializer = new JsonSerializer();

        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('rpop')
            ->with('aQueue')
            ->willReturn(null)
        ;

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

        $consumer = new RedisConsumer($contextMock, $destination);

        $this->assertNull($consumer->receiveNoWait());
    }

    public function testShouldCallRedisRPopAndReturnMessageIfOneInQueueOnReceiveNoWait()
    {
        $destination = new RedisDestination('aQueue');

        $serializer = new JsonSerializer();

        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('rpop')
            ->with('aQueue')
            ->willReturn(new RedisResult('aQueue', $serializer->toString(new RedisMessage('aBody'))))
        ;

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->atLeastOnce())
            ->method('getRedis')
            ->willReturn($redisMock)
        ;
        $contextMock
            ->expects($this->any())
            ->method('getSerializer')
            ->willReturn($serializer)
        ;

        $consumer = new RedisConsumer($contextMock, $destination);

        $message = $consumer->receiveNoWait();

        $this->assertInstanceOf(RedisMessage::class, $message);
        $this->assertSame('aBody', $message->getBody());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Redis
     */
    private function createRedisMock()
    {
        return $this->createMock(Redis::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|RedisProducer
     */
    private function createProducerMock()
    {
        return $this->createMock(RedisProducer::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|RedisContext
     */
    private function createContextMock()
    {
        return $this->createMock(RedisContext::class);
    }
}
