<?php

namespace Enqueue\Consumption;

interface ExtensionInterface extends StartExtensionInterface, PreSubscribeExtensionInterface, PreConsumeExtensionInterface, MessageReceivedExtensionInterface, PostMessageReceivedExtensionInterface, MessageResultExtensionInterface, ProcessorExceptionExtensionInterface
{
    /**
     * Called each time at the end of the cycle if nothing was done.
     *
     * @param Context $context
     */
    public function onIdle(Context $context);

    /**
     * Called when the consumption was interrupted by an extension or exception
     * In case of exception it will be present in the context.
     *
     * @param Context $context
     */
    public function onInterrupted(Context $context);
}
