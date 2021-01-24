<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\SpoolProducer;
use Enqueue\Rpc\Promise;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class SpoolProducerTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementProducerInterface()
    {
        self::assertClassImplements(ProducerInterface::class, SpoolProducer::class);
    }

    public function testCouldBeConstructedWithRealProducer()
    {
        new SpoolProducer($this->createProducerMock());
    }

    public function testShouldQueueEventMessageOnSend()
    {
        $message = new Message();

        $realProducer = $this->createProducerMock();
        $realProducer
            ->expects($this->never())
            ->method('sendEvent')
        ;
        $realProducer
            ->expects($this->never())
            ->method('sendCommand')
        ;

        $producer = new SpoolProducer($realProducer);
        $producer->sendEvent('foo_topic', $message);
        $producer->sendEvent('bar_topic', $message);
    }

    public function testShouldQueueCommandMessageOnSend()
    {
        $message = new Message();

        $realProducer = $this->createProducerMock();
        $realProducer
            ->expects($this->never())
            ->method('sendEvent')
        ;
        $realProducer
            ->expects($this->never())
            ->method('sendCommand')
        ;

        $producer = new SpoolProducer($realProducer);
        $producer->sendCommand('foo_command', $message);
        $producer->sendCommand('bar_command', $message);
    }

    public function testShouldSendQueuedEventMessagesOnFlush()
    {
        $message = new Message();
        $message->setScope('third');

        $realProducer = $this->createProducerMock();
        $realProducer
            ->expects($this->at(0))
            ->method('sendEvent')
            ->with('foo_topic', 'first')
        ;
        $realProducer
            ->expects($this->at(1))
            ->method('sendEvent')
            ->with('bar_topic', ['second'])
        ;
        $realProducer
            ->expects($this->at(2))
            ->method('sendEvent')
            ->with('baz_topic', $this->identicalTo($message))
        ;
        $realProducer
            ->expects($this->never())
            ->method('sendCommand')
        ;

        $producer = new SpoolProducer($realProducer);

        $producer->sendEvent('foo_topic', 'first');
        $producer->sendEvent('bar_topic', ['second']);
        $producer->sendEvent('baz_topic', $message);

        $producer->flush();
    }

    public function testShouldSendQueuedCommandMessagesOnFlush()
    {
        $message = new Message();
        $message->setScope('third');

        $realProducer = $this->createProducerMock();
        $realProducer
            ->expects($this->at(0))
            ->method('sendCommand')
            ->with('foo_command', 'first')
        ;
        $realProducer
            ->expects($this->at(1))
            ->method('sendCommand')
            ->with('bar_command', ['second'])
        ;
        $realProducer
            ->expects($this->at(2))
            ->method('sendCommand')
            ->with('baz_command', $this->identicalTo($message))
        ;

        $producer = new SpoolProducer($realProducer);

        $producer->sendCommand('foo_command', 'first');
        $producer->sendCommand('bar_command', ['second']);
        $producer->sendCommand('baz_command', $message);

        $producer->flush();
    }

    public function testShouldSendImmediatelyCommandMessageWithNeedReplyTrue()
    {
        $message = new Message();
        $message->setScope('third');

        $promise = $this->createMock(Promise::class);

        $realProducer = $this->createProducerMock();
        $realProducer
            ->expects($this->never())
            ->method('sendEvent')
        ;
        $realProducer
            ->expects($this->once())
            ->method('sendCommand')
            ->with('foo_command', 'first')
            ->willReturn($promise)
        ;

        $producer = new SpoolProducer($realProducer);

        $actualPromise = $producer->sendCommand('foo_command', 'first', true);

        $this->assertSame($promise, $actualPromise);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ProducerInterface
     */
    protected function createProducerMock()
    {
        return $this->createMock(ProducerInterface::class);
    }
}
