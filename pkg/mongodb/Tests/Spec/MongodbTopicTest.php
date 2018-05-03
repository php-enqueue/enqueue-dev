<?php

namespace Enqueue\Mongodb\Tests\Spec;

use Enqueue\Mongodb\MongodbDestination;
use Interop\Queue\Spec\PsrTopicSpec;

/**
 * @group mongodb
 */
class MongodbTopicTest extends PsrTopicSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createTopic()
    {
        return new MongodbDestination(self::EXPECTED_TOPIC_NAME);
    }
}
