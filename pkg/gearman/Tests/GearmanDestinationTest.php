<?php

namespace Enqueue\Gearman\Tests;

use Enqueue\Gearman\GearmanDestination;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;
use PHPUnit\Framework\TestCase;

class GearmanDestinationTest extends TestCase
{
    use ClassExtensionTrait;
    use SkipIfGearmanExtensionIsNotInstalledTrait;

    public function testShouldImplementPsrQueueInterface()
    {
        $this->assertClassImplements(PsrQueue::class, GearmanDestination::class);
    }

    public function testShouldImplementPsrTopicInterface()
    {
        $this->assertClassImplements(PsrTopic::class, GearmanDestination::class);
    }

    public function testShouldAllowGetNameSetInConstructor()
    {
        $destination = new GearmanDestination('theDestinationName');

        $this->assertSame('theDestinationName', $destination->getName());
    }
}
