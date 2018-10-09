<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\InitLogger;

interface InitLoggerExtensionInterface
{
    /**
     * Executed only once at the very beginning of the QueueConsumer::consume method call.
     * BEFORE onStart extension method.
     */
    public function onInitLogger(InitLogger $context): void;
}
