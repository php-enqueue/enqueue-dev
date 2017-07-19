<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpTopic;
use Interop\Queue\Spec\PsrTopicSpec;

class AmqpTopicTest extends PsrTopicSpec
{
    protected function createTopic()
    {
        return new AmqpTopic(self::EXPECTED_TOPIC_NAME);
    }
}
