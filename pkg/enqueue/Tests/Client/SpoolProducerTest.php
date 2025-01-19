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

        $invoked = $this->exactly(3);
        $realProducer = $this->createProducerMock();
        $realProducer
            ->expects($invoked)
            ->method('sendEvent')
            ->willReturnCallback(function (string $topic, $argMessage) use ($invoked, $message) {
                match ($invoked->getInvocationCount()) {
                    1 => $this->assertSame(['foo_topic', 'first'], [$topic, $argMessage]),
                    2 => $this->assertSame(['bar_topic', ['second']], [$topic, $argMessage]),
                    3 => $this->assertSame(['baz_topic', $message], [$topic, $argMessage]),
                };
            })
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

        $invoked = $this->exactly(3);
        $realProducer = $this->createProducerMock();
        $realProducer
            ->expects($invoked)
            ->method('sendCommand')
            ->willReturnCallback(function (string $command, $argMessage, bool $needReply) use ($invoked, $message) {
                match ($invoked->getInvocationCount()) {
                    1 => $this->assertSame(['foo_command', 'first', false], [$command, $argMessage, $needReply]),
                    2 => $this->assertSame(['bar_command', ['second'], false], [$command, $argMessage, $needReply]),
                    3 => $this->assertSame(['baz_command', $message, false], [$command, $argMessage, $needReply]),
                };
            })
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
