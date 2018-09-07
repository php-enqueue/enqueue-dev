<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Redis\RedisDestination;
use Interop\Queue\Spec\PsrTopicSpec;

/**
 * @group Redis
 */
class RedisTopicTest extends PsrTopicSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createTopic()
    {
        return new RedisDestination(self::EXPECTED_TOPIC_NAME);
    }
}
