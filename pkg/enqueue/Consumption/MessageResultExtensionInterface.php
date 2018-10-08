<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\MessageResult;

interface MessageResultExtensionInterface
{
    /**
     * Executed when a message is processed by a processor or a result was set in onMessageReceived extension method.
     * BEFORE the message status was sent to the broker
     * The result could be changed at this point.
     */
    public function onResult(MessageResult $context): void;
}
