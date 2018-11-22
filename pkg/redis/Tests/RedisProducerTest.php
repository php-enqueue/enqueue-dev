<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Null\NullMessage;
use Enqueue\Null\NullQueue;
use Enqueue\Redis\JsonSerializer;
use Enqueue\Redis\Redis;
use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisDestination;
use Enqueue\Redis\RedisMessage;
use Enqueue\Redis\RedisProducer;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Producer;
use PHPUnit\Framework\TestCase;

class RedisProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(Producer::class, RedisProducer::class);
    }

    public function testCouldBeConstructedWithRedisAsFirstArgument()
    {
        new RedisProducer($this->createContextMock());
    }

    public function testThrowIfDestinationNotRedisDestinationOnSend()
    {
        $producer = new RedisProducer($this->createContextMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\Redis\RedisDestination but got Enqueue\Null\NullQueue.');
        $producer->send(new NullQueue('aQueue'), new RedisMessage());
    }

    public function testThrowIfMessageNotRedisMessageOnSend()
    {
        $producer = new RedisProducer($this->createContextMock());

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\Redis\RedisMessage but it is Enqueue\Null\NullMessage.');
        $producer->send(new RedisDestination('aQueue'), new NullMessage());
    }

    public function testShouldCallLPushOnSend()
    {
        $destination = new RedisDestination('aDestination');

        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('lpush')
            ->willReturnCallback(function (string $key, string $value) {
                $this->assertSame('aDestination', $key);

                $message = json_decode($value, true);

                $this->assertArrayHasKey('body', $message);
                $this->assertArrayHasKey('properties', $message);
                $this->assertArrayHasKey('headers', $message);
                $this->assertNotEmpty($message['headers']['message_id']);
                $this->assertSame(0, $message['headers']['attempts']);

                return true;
            })
        ;

        $context = $this->createContextMock();
        $context
            ->expects($this->once())
            ->method('getRedis')
            ->willReturn($redisMock)
        ;
        $context
            ->expects($this->once())
            ->method('getSerializer')
            ->willReturn(new JsonSerializer())
        ;

        $producer = new RedisProducer($context);

        $producer->send($destination, new RedisMessage());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RedisContext
     */
    private function createContextMock()
    {
        return $this->createMock(RedisContext::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Redis
     */
    private function createRedisMock()
    {
        return $this->createMock(Redis::class);
    }
}
