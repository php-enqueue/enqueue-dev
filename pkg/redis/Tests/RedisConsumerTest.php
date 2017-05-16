<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Psr\PsrConsumer;
use Enqueue\Redis\Redis;
use Enqueue\Redis\RedisConsumer;
use Enqueue\Redis\RedisContext;
use Enqueue\Redis\RedisDestination;
use Enqueue\Redis\RedisMessage;
use Enqueue\Redis\RedisProducer;
use Enqueue\Test\ClassExtensionTrait;

class RedisConsumerTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConsumerInterface()
    {
        $this->assertClassImplements(PsrConsumer::class, RedisConsumer::class);
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

    public function testShouldDoNothingOnAcknowledge()
    {
        $consumer = new RedisConsumer($this->createContextMock(), new RedisDestination('aQueue'));

        $consumer->acknowledge(new RedisMessage());
    }

    public function testShouldDoNothingOnReject()
    {
        $consumer = new RedisConsumer($this->createContextMock(), new RedisDestination('aQueue'));

        $consumer->reject(new RedisMessage());
    }

    public function testShouldSendSameMessageToDestinationOnReQueue()
    {
        $message = new RedisMessage();

        $destination = new RedisDestination('aQueue');

        $producerMock = $this->createProducerMock();
        $producerMock
            ->expects($this->once())
            ->method('send')
            ->with($this->identicalTo($destination), $this->identicalTo($message))
        ;

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->once())
            ->method('createProducer')
            ->willReturn($producerMock)
        ;

        $consumer = new RedisConsumer($contextMock, $destination);

        $consumer->reject($message, true);
    }

    public function testShouldCallRedisBRPopAndReturnNullIfNothingInQueueOnReceive()
    {
        $destination = new RedisDestination('aQueue');

        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('brpop')
            ->with('aQueue', 2)
            ->willReturn(null)
        ;

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->once())
            ->method('getRedis')
            ->willReturn($redisMock)
        ;

        $consumer = new RedisConsumer($contextMock, $destination);

        $this->assertNull($consumer->receive(2000));
    }

    public function testShouldCallRedisBRPopAndReturnMessageIfOneInQueueOnReceive()
    {
        $destination = new RedisDestination('aQueue');

        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('brpop')
            ->with('aQueue', 2)
            ->willReturn(json_encode(new RedisMessage('aBody')))
        ;

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->once())
            ->method('getRedis')
            ->willReturn($redisMock)
        ;

        $consumer = new RedisConsumer($contextMock, $destination);

        $message = $consumer->receive(2000);

        $this->assertInstanceOf(RedisMessage::class, $message);
        $this->assertSame('aBody', $message->getBody());
    }

    public function testShouldCallRedisRPopAndReturnNullIfNothingInQueueOnReceiveNoWait()
    {
        $destination = new RedisDestination('aQueue');

        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('rpop')
            ->with('aQueue')
            ->willReturn(null)
        ;

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->once())
            ->method('getRedis')
            ->willReturn($redisMock)
        ;

        $consumer = new RedisConsumer($contextMock, $destination);

        $this->assertNull($consumer->receiveNoWait());
    }

    public function testShouldCallRedisRPopAndReturnMessageIfOneInQueueOnReceiveNoWait()
    {
        $destination = new RedisDestination('aQueue');

        $redisMock = $this->createRedisMock();
        $redisMock
            ->expects($this->once())
            ->method('rpop')
            ->with('aQueue')
            ->willReturn(json_encode(new RedisMessage('aBody')))
        ;

        $contextMock = $this->createContextMock();
        $contextMock
            ->expects($this->once())
            ->method('getRedis')
            ->willReturn($redisMock)
        ;

        $consumer = new RedisConsumer($contextMock, $destination);

        $message = $consumer->receiveNoWait();

        $this->assertInstanceOf(RedisMessage::class, $message);
        $this->assertSame('aBody', $message->getBody());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Redis
     */
    private function createRedisMock()
    {
        return $this->createMock(Redis::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RedisProducer
     */
    private function createProducerMock()
    {
        return $this->createMock(RedisProducer::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|RedisContext
     */
    private function createContextMock()
    {
        return $this->createMock(RedisContext::class);
    }
}
