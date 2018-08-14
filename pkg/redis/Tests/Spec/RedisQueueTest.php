<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Redis\RedisDestination;
use Interop\Queue\Spec\PsrQueueSpec;

/**
 * @group Redis
 */
class RedisQueueTest extends PsrQueueSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createQueue()
    {
        return new RedisDestination(self::EXPECTED_QUEUE_NAME);
    }
}
