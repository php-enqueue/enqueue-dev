<?php

namespace Enqueue\Sns\Tests;

use Enqueue\Sns\SnsDestination;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Queue;
use Interop\Queue\Topic;
use PHPUnit\Framework\TestCase;

class SnsDestinationTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementsTopicAndQueueInterfaces()
    {
        $this->assertClassImplements(Topic::class, SnsDestination::class);
        $this->assertClassImplements(Queue::class, SnsDestination::class);
    }

    public function testShouldReturnNameSetInConstructor()
    {
        $destination = new SnsDestination('aDestinationName');

        $this->assertSame('aDestinationName', $destination->getQueueName());
        $this->assertSame('aDestinationName', $destination->getTopicName());
    }

    public function testCouldSetPolicyAttribute()
    {
        $destination = new SnsDestination('aDestinationName');
        $destination->setPolicy('thePolicy');

        $this->assertSame(['Policy' => 'thePolicy'], $destination->getAttributes());
    }

    public function testCouldSetDisplayNameAttribute()
    {
        $destination = new SnsDestination('aDestinationName');
        $destination->setDisplayName('theDisplayName');

        $this->assertSame(['DisplayName' => 'theDisplayName'], $destination->getAttributes());
    }

    public function testCouldSetDeliveryPolicyAttribute()
    {
        $destination = new SnsDestination('aDestinationName');
        $destination->setDeliveryPolicy(123);

        $this->assertSame(['DeliveryPolicy' => 123], $destination->getAttributes());
    }
}
