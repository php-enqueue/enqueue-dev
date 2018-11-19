<?php

namespace Enqueue\Mongodb\Tests;

use Enqueue\Mongodb\MongodbDestination;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Destination;
use Interop\Queue\Queue;
use Interop\Queue\Topic;
use PHPUnit\Framework\TestCase;

/**
 * @group mongodb
 */
class MongodbDestinationTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementDestinationInterface()
    {
        $this->assertClassImplements(Destination::class, MongodbDestination::class);
    }

    public function testShouldImplementTopicInterface()
    {
        $this->assertClassImplements(Topic::class, MongodbDestination::class);
    }

    public function testShouldImplementQueueInterface()
    {
        $this->assertClassImplements(Queue::class, MongodbDestination::class);
    }

    public function testShouldReturnTopicAndQueuePreviouslySetInConstructor()
    {
        $destination = new MongodbDestination('topic-or-queue-name');

        $this->assertSame('topic-or-queue-name', $destination->getName());
        $this->assertSame('topic-or-queue-name', $destination->getTopicName());
    }
}
