<?php

namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaTopic;
use Interop\Queue\Spec\TopicSpec;

/**
 * @group rdkafka
 */
class RdKafkaTopicTest extends TopicSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createTopic()
    {
        return new RdKafkaTopic(self::EXPECTED_TOPIC_NAME);
    }
}
