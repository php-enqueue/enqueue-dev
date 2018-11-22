<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\PreSubscribe;

interface PreSubscribeExtensionInterface
{
    /**
     * The method is called for each BoundProcessor before calling SubscriptionConsumer::subscribe method.
     */
    public function onPreSubscribe(PreSubscribe $context): void;
}
