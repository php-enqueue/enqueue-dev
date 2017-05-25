<?php

namespace Enqueue\Stomp\Tests\Spec;

use Enqueue\Psr\Spec\PsrMessageSpec;
use Enqueue\Stomp\StompMessage;

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
