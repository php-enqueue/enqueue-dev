<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\MessageReceived;

interface MessageReceivedExtensionInterface
{
    /**
     * Executed as soon as a a message is received, before it is passed to a processor
     * The extension may set a result. If the result is set the processor is not called
     * The processor could be changed or decorated at this point.
     */
    public function onMessageReceived(MessageReceived $context): void;
}
