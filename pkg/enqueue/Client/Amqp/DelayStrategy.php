<?php

namespace Enqueue\Client\Amqp;

use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpMessage;

interface DelayStrategy
{
    /**
     * @param AmqpContext     $context
     * @param AmqpDestination $dest
     * @param AmqpMessage     $message
     */
    public function delayMessage(AmqpContext $context, AmqpDestination $dest, AmqpMessage $message);
}
