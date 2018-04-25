<?php

namespace Enqueue\Gps\Tests\Spec;

use Enqueue\Gps\GpsTopic;
use Interop\Queue\Spec\PsrTopicSpec;

class GpsTopicTest extends PsrTopicSpec
{
    protected function createTopic()
    {
        return new GpsTopic(self::EXPECTED_TOPIC_NAME);
    }
}
