<?php

namespace Enqueue\Dbal\Tests\Spec;

use Enqueue\Dbal\DbalDestination;
use Interop\Queue\Spec\QueueSpec;

class DbalQueueTest extends QueueSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createQueue()
    {
        return new DbalDestination(self::EXPECTED_QUEUE_NAME);
    }
}
