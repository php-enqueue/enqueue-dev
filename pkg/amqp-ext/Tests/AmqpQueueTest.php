<?php
namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpQueue;
use Enqueue\Psr\Queue;
use Enqueue\Test\ClassExtensionTrait;

class AmqpQueueTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementQueueInterface()
    {
        $this->assertClassImplements(Queue::class, AmqpQueue::class);
    }

    public function testCouldBeConstructedWithQueueNameArgument()
    {
        new AmqpQueue('aName');
    }

    public function testShouldReturnQueueNameSetInConstructor()
    {
        $queue = new AmqpQueue('theName');

        $this->assertSame('theName', $queue->getQueueName());
    }

    public function testShouldReturnPreviouslySetQueueName()
    {
        $queue = new AmqpQueue('aName');

        $queue->setQueueName('theAnotherQueueName');

        $this->assertSame('theAnotherQueueName', $queue->getQueueName());
    }

    public function testShouldSetEmptyArrayAsArgumentsInConstructor()
    {
        $queue = new AmqpQueue('aName');

        $this->assertSame([], $queue->getArguments());
    }

    public function testShouldSetEmptyArrayAsBindArgumentsInConstructor()
    {
        $queue = new AmqpQueue('aName');

        $this->assertSame([], $queue->getBindArguments());
    }

    public function testShouldSetNoParamFlagInConstructor()
    {
        $queue = new AmqpQueue('aName');

        $this->assertSame(AMQP_NOPARAM, $queue->getFlags());
    }

    public function testShouldAllowAddFlags()
    {
        $queue = new AmqpQueue('aName');

        $queue->addFlag(AMQP_DURABLE);
        $queue->addFlag(AMQP_PASSIVE);

        $this->assertSame(AMQP_DURABLE | AMQP_PASSIVE, $queue->getFlags());
    }

    public function testShouldClearPreviouslySetFlags()
    {
        $queue = new AmqpQueue('aName');

        $queue->addFlag(AMQP_DURABLE);
        $queue->addFlag(AMQP_PASSIVE);

        //guard
        $this->assertSame(AMQP_DURABLE | AMQP_PASSIVE, $queue->getFlags());

        $queue->clearFlags();

        $this->assertSame(AMQP_NOPARAM, $queue->getFlags());
    }

    public function testShouldAllowGetPreviouslySetArguments()
    {
        $queue = new AmqpQueue('aName');

        $queue->setArguments(['foo' => 'fooVal', 'bar' => 'barVal']);

        $this->assertSame(['foo' => 'fooVal', 'bar' => 'barVal'], $queue->getArguments());
    }

    public function testShouldAllowGetPreviouslySetBindArguments()
    {
        $queue = new AmqpQueue('aName');

        $queue->setBindArguments(['foo' => 'fooVal', 'bar' => 'barVal']);

        $this->assertSame(['foo' => 'fooVal', 'bar' => 'barVal'], $queue->getBindArguments());
    }
}
