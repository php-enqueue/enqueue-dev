<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Mongodb\MongodbDestination;
use Interop\Queue\Spec\QueueSpec;

/**
 * @group mongodb
 */
class MongodbQueueTest extends QueueSpec
{
    protected function createQueue()
    {
        return new MongodbDestination(self::EXPECTED_QUEUE_NAME);
    }
}
