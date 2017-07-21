<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpQueue;
use Interop\Queue\Spec\PsrQueueSpec;

class AmqpQueueTest extends PsrQueueSpec
{
    protected function createQueue()
    {
        return new AmqpQueue(self::EXPECTED_QUEUE_NAME);
    }
}
