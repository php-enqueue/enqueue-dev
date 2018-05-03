<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Mongodb\MongodbDestination;
use Interop\Queue\Spec\PsrQueueSpec;

/**
 * @group mongodb
 */
class MongodbQueueTest extends PsrQueueSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createQueue()
    {
        return new MongodbDestination(self::EXPECTED_QUEUE_NAME);
    }
}
