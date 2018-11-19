<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\PostMessageReceived;

interface PostMessageReceivedExtensionInterface
{
    /**
     * Executed at the very end of consumption callback. The message has already been acknowledged.
     * The message result could not be changed.
     * The consumption could be interrupted at this point.
     */
    public function onPostMessageReceived(PostMessageReceived $context): void;
}
