<?php

namespace Enqueue\Wamp\Tests\Spec;

use Enqueue\Wamp\WampDestination;
use Interop\Queue\Spec\TopicSpec;

/**
 * @group Wamp
 */
class WampTopicTest extends TopicSpec
{
    protected function createTopic()
    {
        return new WampDestination(self::EXPECTED_TOPIC_NAME);
    }
}
