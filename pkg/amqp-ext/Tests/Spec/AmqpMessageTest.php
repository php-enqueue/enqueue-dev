<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Enqueue\AmqpExt\AmqpMessage;
use Interop\Queue\Spec\PsrMessageSpec;

class AmqpMessageTest extends PsrMessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new AmqpMessage();
    }
}
