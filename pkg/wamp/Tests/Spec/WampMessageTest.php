<?php

namespace Enqueue\Wamp\Tests\Spec;

use Enqueue\Wamp\WampMessage;
use Interop\Queue\Spec\MessageSpec;

/**
 * @group Wamp
 */
class WampMessageTest extends MessageSpec
{
    protected function createMessage()
    {
        return new WampMessage();
    }
}
