<?php

namespace Enqueue\NoEffect\Tests;

use Enqueue\NoEffect\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Queue;
use PHPUnit\Framework\TestCase;

class NullQueueTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementQueueInterface()
    {
        $this->assertClassImplements(Queue::class, NullQueue::class);
    }

    public function testCouldBeConstructedWithNameAsArgument()
    {
        new NullQueue('aName');
    }

    public function testShouldAllowGetNameSetInConstructor()
    {
        $queue = new NullQueue('theName');

        $this->assertEquals('theName', $queue->getQueueName());
    }
}
