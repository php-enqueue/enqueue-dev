<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanDestination;
use Enqueue\Gearman\Tests\SkipIfGearmanExtensionIsNotInstalledTrait;
use Enqueue\Psr\Spec\PsrQueueSpec;

class GearmanQueueTest extends PsrQueueSpec
{
    use SkipIfGearmanExtensionIsNotInstalledTrait;

    /**
     * {@inheritdoc}
     */
    protected function createQueue()
    {
        return new GearmanDestination(self::EXPECTED_QUEUE_NAME);
    }
}
