<?php

namespace Enqueue\Gearman\Tests\Spec;

use Enqueue\Gearman\GearmanDestination;
use Enqueue\Psr\Spec\PsrQueueSpec;

/**
 * @group functional
 */
class GearmanQueueTest extends PsrQueueSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createQueue()
    {
        return new GearmanDestination(self::EXPECTED_QUEUE_NAME);
    }
}
