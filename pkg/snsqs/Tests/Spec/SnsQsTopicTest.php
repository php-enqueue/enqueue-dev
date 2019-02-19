<?php

namespace Enqueue\SnsQs\Tests\Spec;

use Enqueue\SnsQs\SnsQsTopic;
use Interop\Queue\Spec\TopicSpec;

class SnsQsTopicTest extends TopicSpec
{
    protected function createTopic()
    {
        return new SnsQsTopic(self::EXPECTED_TOPIC_NAME);
    }
}
