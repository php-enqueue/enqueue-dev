<?php

namespace Enqueue\Gps\Tests\Spec;

use Enqueue\Gps\GpsQueue;
use Interop\Queue\Spec\PsrQueueSpec;

class GpsQueueTest extends PsrQueueSpec
{
    protected function createQueue()
    {
        return new GpsQueue(self::EXPECTED_QUEUE_NAME);
    }
}
