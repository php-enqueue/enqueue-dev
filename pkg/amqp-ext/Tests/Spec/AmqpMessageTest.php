<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Interop\Amqp\Impl\AmqpMessage;
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
