<?php

namespace Enqueue\AmqpTools;

use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpMessage;

interface DelayStrategy
{
    /**
     * @param AmqpContext     $context
     * @param AmqpDestination $dest
     * @param AmqpMessage     $message
     * @param int             $delayMsec
     */
    public function delayMessage(AmqpContext $context, AmqpDestination $dest, AmqpMessage $message, $delayMsec);
}
