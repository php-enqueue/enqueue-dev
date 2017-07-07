<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpTopic;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrTopic;
use PHPUnit\Framework\TestCase;

class AmqpTopicTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTopicInterface()
    {
        $this->assertClassImplements(PsrTopic::class, AmqpTopic::class);
    }

    public function testCouldBeConstructedWithTopicNameAsArgument()
    {
        new AmqpTopic('aName');
    }

    public function testShouldReturnTopicNameSetInConstructor()
    {
        $topic = new AmqpTopic('theName');

        $this->assertSame('theName', $topic->getTopicName());
    }

    public function testShouldReturnPreviouslySetTopicName()
    {
        $topic = new AmqpTopic('aName');

        $topic->setTopicName('theAnotherTopicName');

        $this->assertSame('theAnotherTopicName', $topic->getTopicName());
    }

    public function testShouldSetEmptyArrayAsArgumentsInConstructor()
    {
        $topic = new AmqpTopic('aName');

        $this->assertSame([], $topic->getArguments());
    }

    public function testShouldSetDirectTypeInConstructor()
    {
        $topic = new AmqpTopic('aName');

        $this->assertSame(\AMQP_EX_TYPE_DIRECT, $topic->getType());
    }

    public function testShouldSetNoParamFlagInConstructor()
    {
        $topic = new AmqpTopic('aName');

        $this->assertSame(AMQP_NOPARAM, $topic->getFlags());
    }

    public function testShouldAllowAddFlags()
    {
        $topic = new AmqpTopic('aName');

        $topic->addFlag(AMQP_DURABLE);
        $topic->addFlag(AMQP_PASSIVE);

        $this->assertSame(AMQP_DURABLE | AMQP_PASSIVE, $topic->getFlags());
    }

    public function testShouldClearPreviouslySetFlags()
    {
        $topic = new AmqpTopic('aName');

        $topic->addFlag(AMQP_DURABLE);
        $topic->addFlag(AMQP_PASSIVE);

        //guard
        $this->assertSame(AMQP_DURABLE | AMQP_PASSIVE, $topic->getFlags());

        $topic->clearFlags();

        $this->assertSame(AMQP_NOPARAM, $topic->getFlags());
    }

    public function testShouldAllowGetPreviouslySetArguments()
    {
        $topic = new AmqpTopic('aName');

        $topic->setArguments(['foo' => 'fooVal', 'bar' => 'barVal']);

        $this->assertSame(['foo' => 'fooVal', 'bar' => 'barVal'], $topic->getArguments());
    }

    public function testShouldAllowGetPreviouslySetType()
    {
        $topic = new AmqpTopic('aName');

        $topic->setType(\AMQP_EX_TYPE_FANOUT);

        $this->assertSame(\AMQP_EX_TYPE_FANOUT, $topic->getType());
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
