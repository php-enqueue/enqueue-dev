<?php

namespace Enqueue\Dbal\Tests;

use Enqueue\Dbal\DbalDestination;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Destination;
use Interop\Queue\Queue;
use Interop\Queue\Topic;
use PHPUnit\Framework\TestCase;

class DbalDestinationTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementDestinationInterface()
    {
        $this->assertClassImplements(Destination::class, DbalDestination::class);
    }

    public function testShouldImplementTopicInterface()
    {
        $this->assertClassImplements(Topic::class, DbalDestination::class);
    }

    public function testShouldImplementQueueInterface()
    {
        $this->assertClassImplements(Queue::class, DbalDestination::class);
    }

    public function testShouldReturnTopicAndQueuePreviouslySetInConstructor()
    {
        $destination = new DbalDestination('topic-or-queue-name');

        $this->assertSame('topic-or-queue-name', $destination->getQueueName());
        $this->assertSame('topic-or-queue-name', $destination->getTopicName());
    }
}
