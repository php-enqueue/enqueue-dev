<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\Start;

interface StartExtensionInterface
{
    /**
     * Executed only once at the very beginning of the QueueConsumer::consume method call.
     */
    public function onStart(Start $context): void;
}
