<?php

namespace Enqueue\Tests\Client;

use Enqueue\Client\Message;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\SpoolProducer;
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

    public function testShouldQueueMessageOnSend()
    {
        $message = new Message();

        $realProducer = $this->createProducerMock();
        $realProducer
            ->expects($this->never())
            ->method('send')
        ;

        $producer = new SpoolProducer($realProducer);
        $producer->send('foo_topic', $message);
        $producer->send('bar_topic', $message);
    }

    public function testShouldSendQueuedMessagesOnFlush()
    {
        $message = new Message();
        $message->setScope('third');

        $realProducer = $this->createProducerMock();
        $realProducer
            ->expects($this->at(0))
            ->method('send')
            ->with('foo_topic', 'first')
        ;
        $realProducer
            ->expects($this->at(1))
            ->method('send')
            ->with('bar_topic', ['second'])
        ;
        $realProducer
            ->expects($this->at(2))
            ->method('send')
            ->with('baz_topic', $this->identicalTo($message))
        ;

        $producer = new SpoolProducer($realProducer);

        $producer->send('foo_topic', 'first');
        $producer->send('bar_topic', ['second']);
        $producer->send('baz_topic', $message);

        $producer->flush();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ProducerInterface
     */
    protected function createProducerMock()
    {
        return $this->createMock(ProducerInterface::class);
    }
}
