<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Redis\RedisDestination;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;

class RedisDestinationTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementsTopicAndQueueInterfaces()
    {
        $this->assertClassImplements(PsrTopic::class, RedisDestination::class);
        $this->assertClassImplements(PsrQueue::class, RedisDestination::class);
    }

    public function testShouldReturnNameSetInConstructor()
    {
        $destination = new RedisDestination('aDestinationName');

        $this->assertSame('aDestinationName', $destination->getName());
        $this->assertSame('aDestinationName', $destination->getQueueName());
        $this->assertSame('aDestinationName', $destination->getTopicName());
    }
}
