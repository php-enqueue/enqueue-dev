<?php

namespace Enqueue\Dbal\Tests\Spec;

use Enqueue\Dbal\DbalDestination;
use Interop\Queue\Spec\PsrQueueSpec;

class DbalQueueTest extends PsrQueueSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createQueue()
    {
        return new DbalDestination(self::EXPECTED_QUEUE_NAME);
    }
}
