<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanDestination;
use Enqueue\Gearman\Tests\SkipIfGearmanExtensionIsNotInstalledTrait;
use Interop\Queue\Spec\QueueSpec;

class GearmanQueueTest extends QueueSpec
{
    use SkipIfGearmanExtensionIsNotInstalledTrait;

    protected function createQueue()
    {
        return new GearmanDestination(self::EXPECTED_QUEUE_NAME);
    }
}
