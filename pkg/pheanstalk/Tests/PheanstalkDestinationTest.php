<?php

namespace Enqueue\Pheanstalk\Tests;

use Enqueue\Pheanstalk\PheanstalkDestination;
use Enqueue\Psr\PsrQueue;
use Enqueue\Psr\PsrTopic;
use Enqueue\Test\ClassExtensionTrait;
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
        $destionation = new PheanstalkDestination('theDestinationName');

        $this->assertSame('theDestinationName', $destionation->getName());
    }
}
