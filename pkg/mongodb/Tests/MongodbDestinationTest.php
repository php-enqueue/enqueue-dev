<?php

namespace Enqueue\Mongodb\Tests;

use Enqueue\Mongodb\MongodbDestination;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;

/**
 * @group mongodb
 */
class MongodbDestinationTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementDestinationInterface()
    {
        $this->assertClassImplements(PsrDestination::class, MongodbDestination::class);
    }

    public function testShouldImplementTopicInterface()
    {
        $this->assertClassImplements(PsrTopic::class, MongodbDestination::class);
    }

    public function testShouldImplementQueueInterface()
    {
        $this->assertClassImplements(PsrQueue::class, MongodbDestination::class);
    }

    public function testShouldReturnTopicAndQueuePreviouslySetInConstructor()
    {
        $destination = new MongodbDestination('topic-or-queue-name');

        $this->assertSame('topic-or-queue-name', $destination->getName());
        $this->assertSame('topic-or-queue-name', $destination->getTopicName());
    }
}
