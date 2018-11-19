<?php

namespace Enqueue\Redis\Tests\Spec;

use Enqueue\Redis\RedisDestination;
use Interop\Queue\Spec\TopicSpec;

/**
 * @group Redis
 */
class RedisTopicTest extends TopicSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createTopic()
    {
        return new RedisDestination(self::EXPECTED_TOPIC_NAME);
    }
}
