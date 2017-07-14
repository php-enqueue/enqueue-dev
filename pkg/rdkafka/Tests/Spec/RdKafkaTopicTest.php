<?php

namespace Enqueue\RdKafka\Tests\Spec;

use Enqueue\RdKafka\RdKafkaTopic;
use Interop\Queue\Spec\PsrTopicSpec;

class RdKafkaTopicTest extends PsrTopicSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createTopic()
    {
        return new RdKafkaTopic(self::EXPECTED_TOPIC_NAME);
    }
}
