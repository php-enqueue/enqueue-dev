<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\End;

interface EndExtensionInterface
{
    /**
     * Executed only once just before QueueConsumer::consume returns.
     */
    public function onEnd(End $context): void;
}
