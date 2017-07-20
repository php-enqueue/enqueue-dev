<?php

namespace Enqueue\AmqpLib\Tests;

use Enqueue\AmqpLib\AmqpQueue;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class AmqpQueueTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldAllowGetPreviouslySetPassive()
    {
        $topic = new AmqpQueue('aName');

        $topic->setPassive(false);
        $this->assertFalse($topic->isPassive());

        $topic->setPassive(true);
        $this->assertTrue($topic->isPassive());
    }

    public function testShouldAllowGetPreviouslySetDurable()
    {
        $topic = new AmqpQueue('aName');

        $topic->setDurable(false);
        $this->assertFalse($topic->isDurable());

        $topic->setDurable(true);
        $this->assertTrue($topic->isDurable());
    }

    public function testShouldAllowGetPreviouslySetExclusive()
    {
        $topic = new AmqpQueue('aName');

        $topic->setExclusive(false);
        $this->assertFalse($topic->isExclusive());

        $topic->setExclusive(true);
        $this->assertTrue($topic->isExclusive());
    }

    public function testShouldAllowGetPreviouslySetAutoDelete()
    {
        $topic = new AmqpQueue('aName');

        $topic->setAutoDelete(false);
        $this->assertFalse($topic->isAutoDelete());

        $topic->setAutoDelete(true);
        $this->assertTrue($topic->isAutoDelete());
    }

    public function testShouldAllowGetPreviouslySetNoWait()
    {
        $topic = new AmqpQueue('aName');

        $topic->setNoWait(false);
        $this->assertFalse($topic->isNoWait());

        $topic->setNoWait(true);
        $this->assertTrue($topic->isNoWait());
    }

    public function testShouldAllowGetPreviouslySetArguments()
    {
        $queue = new AmqpQueue('aName');

        $queue->setArguments(['foo' => 'fooVal', 'bar' => 'barVal']);

        $this->assertSame(['foo' => 'fooVal', 'bar' => 'barVal'], $queue->getArguments());
    }

    public function testShouldAllowGetPreviouslySetTicket()
    {
        $topic = new AmqpQueue('aName');

        //guard
        $this->assertSame(null, $topic->getTicket());

        $topic->setTicket('ticket');

        $this->assertSame('ticket', $topic->getTicket());
    }

    public function testShouldAllowGetPreviouslySetConsumerTag()
    {
        $topic = new AmqpQueue('aName');

        //guard
        $this->assertSame(null, $topic->getConsumerTag());

        $topic->setConsumerTag('consumer-tag');

        $this->assertSame('consumer-tag', $topic->getConsumerTag());
    }

    public function testShouldAllowGetPreviouslySetNoLocal()
    {
        $topic = new AmqpQueue('aName');

        $topic->setNoLocal(false);
        $this->assertFalse($topic->isNoLocal());

        $topic->setNoLocal(true);
        $this->assertTrue($topic->isNoLocal());
    }

    public function testShouldAllowGetPreviouslySetNoAck()
    {
        $topic = new AmqpQueue('aName');

        $topic->setNoAck(false);
        $this->assertFalse($topic->isNoAck());

        $topic->setNoAck(true);
        $this->assertTrue($topic->isNoAck());
    }
}
