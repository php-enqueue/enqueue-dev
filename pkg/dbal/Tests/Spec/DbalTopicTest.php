<?php

namespace Enqueue\Dbal\Tests\Spec;

use Enqueue\Dbal\DbalDestination;
use Interop\Queue\Spec\TopicSpec;

class DbalTopicTest extends TopicSpec
{
    protected function createTopic()
    {
        return new DbalDestination(self::EXPECTED_TOPIC_NAME);
    }
}
