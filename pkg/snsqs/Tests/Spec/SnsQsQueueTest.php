<?php

namespace Enqueue\SnsQs\Tests\Spec;

use Enqueue\SnsQs\SnsQsQueue;
use Interop\Queue\Spec\QueueSpec;

class SnsQsQueueTest extends QueueSpec
{
    protected function createQueue()
    {
        return new SnsQsQueue(self::EXPECTED_QUEUE_NAME);
    }
}
