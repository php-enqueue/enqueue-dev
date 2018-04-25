<?php

namespace Enqueue\Pheanstalk\Tests;

use Enqueue\Pheanstalk\PheanstalkDestination;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;
use PHPUnit\Framework\TestCase;

class PheanstalkDestinationTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementPsrQueueInterface()
    {
        $this->assertClassImplements(PsrQueue::class, PheanstalkDestination::class);
    }

    public function testShouldImplementPsrTopicInterface()
    {
        $this->assertClassImplements(PsrTopic::class, PheanstalkDestination::class);
    }

    public function testShouldAllowGetNameSetInConstructor()
    {
        $destination = new PheanstalkDestination('theDestinationName');

        $this->assertSame('theDestinationName', $destination->getName());
    }
}
