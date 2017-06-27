<?php

namespace Enqueue\Gearman\Tests;

use Enqueue\Gearman\GearmanDestination;
use Enqueue\Psr\PsrQueue;
use Enqueue\Psr\PsrTopic;
use Enqueue\Test\ClassExtensionTrait;
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
