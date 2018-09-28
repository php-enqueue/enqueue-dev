<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Mongodb\MongodbDestination;
use Interop\Queue\Spec\TopicSpec;

/**
 * @group mongodb
 */
class MongodbTopicTest extends TopicSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createTopic()
    {
        return new MongodbDestination(self::EXPECTED_TOPIC_NAME);
    }
}
