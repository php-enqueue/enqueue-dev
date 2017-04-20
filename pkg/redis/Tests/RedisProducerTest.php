<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\InvalidMessageException;
use Enqueue\Psr\PsrProducer;
use Enqueue\Redis\Redis;
use Enqueue\Redis\RedisDestination;
use Enqueue\Redis\RedisMessage;
use Enqueue\Redis\RedisProducer;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Transport\Null\NullMessage;
use Enqueue\Transport\Null\NullQueue;
use Makasim\File\TempFile;
use PHPUnit\Framework\TestCase;

class RedisProducerTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        $this->assertClassImplements(PsrProducer::class, RedisProducer::class);
    }

    public function testCouldBeConstructedWithRedisAsFirstArgument()
    {
        new RedisProducer($this->createRedisMock());
    }

    public function testThrowIfDestinationNotRedisDestinationOnSend()
    {
        $producer = new RedisProducer($this->createRedisMock());

        $this->expectException(InvalidDestinationException::class);
        $this->expectExceptionMessage('The destination must be an instance of Enqueue\Redis\RedisDestination but got Enqueue\Transport\Null\NullQueue.');
        $producer->send(new NullQueue('aQueue'), new RedisMessage());
    }

    public function testThrowIfMessageNotRedisMessageOnSend()
    {
        $producer = new RedisProducer($this->createRedisMock());

        $this->expectException(InvalidMessageException::class);
        $this->expectExceptionMessage('The message must be an instance of Enqueue\Redis\RedisMessage but it is Enqueue\Transport\Null\NullMessage.');
        $producer->send(new RedisDestination(TempFile::generate()), new NullMessage());
    }

    public function testShouldCallLPushOnSend()
    {
        $destination = new RedisDestination('aDestination');

        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('lpush')
            ->with('aDestination', '{"body":null,"properties":[],"headers":[]}')
        ;

        $producer = new RedisProducer($redisMock);

        $producer->send($destination, new RedisMessage());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Redis
     */
    private function createRedisMock()
    {
        return $this->createMock(Redis::class);
    }
}
