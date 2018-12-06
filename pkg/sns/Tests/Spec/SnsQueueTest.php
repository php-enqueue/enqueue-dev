<?php

namespace Enqueue\Sns\Tests\Spec;

use Enqueue\Sns\SnsDestination;
use Interop\Queue\Spec\QueueSpec;

class SnsQueueTest extends QueueSpec
{
    protected function createQueue()
    {
        return new SnsDestination(self::EXPECTED_QUEUE_NAME);
    }
}
