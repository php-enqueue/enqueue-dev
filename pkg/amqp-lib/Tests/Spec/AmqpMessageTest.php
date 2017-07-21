<?php

namespace Enqueue\AmqpLib\Tests\Spec;

use Enqueue\AmqpLib\AmqpMessage;
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
