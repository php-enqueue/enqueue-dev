<?php

namespace Enqueue\Stomp\Tests\Spec;

use Enqueue\Stomp\StompMessage;
use Interop\Queue\Spec\MessageSpec;

class StompMessageTest extends MessageSpec
{
    protected function createMessage()
    {
        return new StompMessage();
    }
}
