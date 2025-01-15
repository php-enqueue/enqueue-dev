<?php

namespace Enqueue\Wamp\Tests\Spec;

use Enqueue\Wamp\WampDestination;
use Interop\Queue\Spec\QueueSpec;

/**
 * @group Wamp
 */
class WampQueueTest extends QueueSpec
{
    protected function createQueue()
    {
        return new WampDestination(self::EXPECTED_QUEUE_NAME);
    }
}
