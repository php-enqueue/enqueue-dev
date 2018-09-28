<?php

namespace Enqueue\Pheanstalk\Tests;

use Enqueue\Pheanstalk\PheanstalkDestination;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Queue;
use Interop\Queue\Topic;
use PHPUnit\Framework\TestCase;

class PheanstalkDestinationTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementQueueInterface()
    {
        $this->assertClassImplements(Queue::class, PheanstalkDestination::class);
    }

    public function testShouldImplementTopicInterface()
    {
        $this->assertClassImplements(Topic::class, PheanstalkDestination::class);
    }

    public function testShouldAllowGetNameSetInConstructor()
    {
        $destination = new PheanstalkDestination('theDestinationName');

        $this->assertSame('theDestinationName', $destination->getName());
    }
}
