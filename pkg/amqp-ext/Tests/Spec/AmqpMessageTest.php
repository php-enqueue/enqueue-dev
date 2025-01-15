<?php

namespace Enqueue\AmqpExt\Tests\Spec;

use Interop\Amqp\Impl\AmqpMessage;
use Interop\Queue\Spec\MessageSpec;

class AmqpMessageTest extends MessageSpec
{
    protected function createMessage()
    {
        return new AmqpMessage();
    }
}
