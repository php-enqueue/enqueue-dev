<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\PreConsume;

interface PreConsumeExtensionInterface
{
    /**
     * Executed at every new cycle before calling SubscriptionConsumer::consume method.
     * The consumption could be interrupted at this step.
     */
    public function onPreConsume(PreConsume $context): void;
}
