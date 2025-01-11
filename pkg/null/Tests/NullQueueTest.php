<?php

namespace Enqueue\Null\Tests;

use Enqueue\Null\NullQueue;
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

    /**
     * @doesNotPerformAssertions
     */
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
