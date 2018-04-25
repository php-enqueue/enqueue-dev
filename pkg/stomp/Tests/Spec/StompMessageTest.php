<?php

namespace Enqueue\Stomp\Tests\Spec;

use Enqueue\Stomp\StompMessage;
use Interop\Queue\Spec\PsrMessageSpec;

class StompMessageTest extends PsrMessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new StompMessage();
    }
}
