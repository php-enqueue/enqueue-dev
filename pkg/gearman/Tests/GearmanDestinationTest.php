<?php

namespace Enqueue\Gearman\Tests;

use Enqueue\Gearman\GearmanDestination;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Queue;
use Interop\Queue\Topic;
use PHPUnit\Framework\TestCase;

class GearmanDestinationTest extends TestCase
{
    use ClassExtensionTrait;
    use SkipIfGearmanExtensionIsNotInstalledTrait;

    public function testShouldImplementQueueInterface()
    {
        $this->assertClassImplements(Queue::class, GearmanDestination::class);
    }

    public function testShouldImplementTopicInterface()
    {
        $this->assertClassImplements(Topic::class, GearmanDestination::class);
    }

    public function testShouldAllowGetNameSetInConstructor()
    {
        $destination = new GearmanDestination('theDestinationName');

        $this->assertSame('theDestinationName', $destination->getName());
    }
}
