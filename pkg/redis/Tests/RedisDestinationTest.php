<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Psr\PsrQueue;
use Enqueue\Psr\PsrTopic;
use Enqueue\Redis\RedisDestination;
use Enqueue\Test\ClassExtensionTrait;

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
