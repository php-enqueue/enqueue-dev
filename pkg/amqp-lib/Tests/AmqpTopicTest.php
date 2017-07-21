<?php

namespace Enqueue\AmqpLib\Tests;

use Enqueue\AmqpLib\AmqpTopic;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

class AmqpTopicTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldSetDirectTypeInConstructor()
    {
        $topic = new AmqpTopic('aName');

        $this->assertSame('direct', $topic->getType());
    }

    public function testShouldAllowGetPreviouslySetType()
    {
        $topic = new AmqpTopic('aName');

        $topic->setType('fanout');

        $this->assertSame('fanout', $topic->getType());
    }

    public function testShouldAllowGetPreviouslySetPassive()
    {
        $topic = new AmqpTopic('aName');

        $topic->setPassive(false);
        $this->assertFalse($topic->isPassive());

        $topic->setPassive(true);
        $this->assertTrue($topic->isPassive());
    }

    public function testShouldAllowGetPreviouslySetDurable()
    {
        $topic = new AmqpTopic('aName');

        $topic->setDurable(false);
        $this->assertFalse($topic->isDurable());

        $topic->setDurable(true);
        $this->assertTrue($topic->isDurable());
    }

    public function testShouldAllowGetPreviouslySetAutoDelete()
    {
        $topic = new AmqpTopic('aName');

        $topic->setAutoDelete(false);
        $this->assertFalse($topic->isAutoDelete());

        $topic->setAutoDelete(true);
        $this->assertTrue($topic->isAutoDelete());
    }

    public function testShouldAllowGetPreviouslySetInternal()
    {
        $topic = new AmqpTopic('aName');

        $topic->setInternal(false);
        $this->assertFalse($topic->isInternal());

        $topic->setInternal(true);
        $this->assertTrue($topic->isInternal());
    }

    public function testShouldAllowGetPreviouslySetNoWait()
    {
        $topic = new AmqpTopic('aName');

        $topic->setNoWait(false);
        $this->assertFalse($topic->isNoWait());

        $topic->setNoWait(true);
        $this->assertTrue($topic->isNoWait());
    }

    public function testShouldAllowGetPreviouslySetArguments()
    {
        $topic = new AmqpTopic('aName');

        $topic->setArguments(['foo' => 'fooVal', 'bar' => 'barVal']);

        $this->assertSame(['foo' => 'fooVal', 'bar' => 'barVal'], $topic->getArguments());
    }

    public function testShouldAllowGetPreviouslySetTicket()
    {
        $topic = new AmqpTopic('aName');

        //guard
        $this->assertSame(null, $topic->getTicket());

        $topic->setTicket('ticket');

        $this->assertSame('ticket', $topic->getTicket());
    }

    public function testShouldAllowGetPreviouslySetRoutingKey()
    {
        $topic = new AmqpTopic('aName');

        //guard
        $this->assertSame(null, $topic->getRoutingKey());

        $topic->setRoutingKey('theRoutingKey');

        $this->assertSame('theRoutingKey', $topic->getRoutingKey());
    }
}
