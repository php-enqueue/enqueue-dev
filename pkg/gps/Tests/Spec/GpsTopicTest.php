<?php

namespace Enqueue\Gps\Tests\Spec;

use Enqueue\Gps\GpsTopic;
use Interop\Queue\Spec\TopicSpec;

class GpsTopicTest extends TopicSpec
{
    protected function createTopic()
    {
        return new GpsTopic(self::EXPECTED_TOPIC_NAME);
    }
}
