<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Redis\RedisDestination;
use Interop\Queue\Spec\QueueSpec;

/**
 * @group Redis
 */
class RedisQueueTest extends QueueSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createQueue()
    {
        return new RedisDestination(self::EXPECTED_QUEUE_NAME);
    }
}
