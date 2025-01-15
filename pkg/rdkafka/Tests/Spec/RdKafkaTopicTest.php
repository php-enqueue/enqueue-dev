<?php

namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaTopic;
use Interop\Queue\Spec\TopicSpec;

class RdKafkaTopicTest extends TopicSpec
{
    protected function createTopic()
    {
        return new RdKafkaTopic(self::EXPECTED_TOPIC_NAME);
    }
}
