<?php

namespace Enqueue\Null\Tests;

use Enqueue\Null\NullQueue;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrQueue;
use PHPUnit\Framework\TestCase;

class NullQueueTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementQueueInterface()
    {
        $this->assertClassImplements(PsrQueue::class, NullQueue::class);
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
