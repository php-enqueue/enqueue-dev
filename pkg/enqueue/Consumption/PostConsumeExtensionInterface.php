<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\PostConsume;

interface PostConsumeExtensionInterface
{
    /**
     * The method is called after SubscriptionConsumer::consume method exits.
     * The consumption could be interrupted at this point.
     */
    public function onPostConsume(PostConsume $context): void;
}
