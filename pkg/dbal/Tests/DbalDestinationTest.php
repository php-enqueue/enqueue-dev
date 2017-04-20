<?php
namespace Enqueue\Dbal\Tests;

use Enqueue\Dbal\DbalDestination;
use Enqueue\Psr\PsrDestination;
use Enqueue\Psr\PsrQueue;
use Enqueue\Psr\PsrTopic;
use Enqueue\Test\ClassExtensionTrait;

class DbalDestinationTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementDestinationInterface()
    {
        $this->assertClassImplements(PsrDestination::class, DbalDestination::class);
    }

    public function testShouldImplementTopicInterface()
    {
        $this->assertClassImplements(PsrTopic::class, DbalDestination::class);
    }

    public function testShouldImplementQueueInterface()
    {
        $this->assertClassImplements(PsrQueue::class, DbalDestination::class);
    }

    public function testShouldReturnTopicAndQueuePreviouslySetInConstructor()
    {
        $destination = new DbalDestination('topic-or-queue-name');

        $this->assertSame('topic-or-queue-name', $destination->getQueueName());
        $this->assertSame('topic-or-queue-name', $destination->getTopicName());
    }
}
