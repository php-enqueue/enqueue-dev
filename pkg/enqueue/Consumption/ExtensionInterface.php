<?php

namespace Enqueue\Consumption;

interface ExtensionInterface extends StartExtensionInterface, PreSubscribeExtensionInterface, PreConsumeExtensionInterface, MessageReceivedExtensionInterface, MessageResultExtensionInterface, ProcessorExceptionExtensionInterface
{
    /**
     * Executed when a message is processed by a processor.
     * The context contains a status, which could not be changed.
     * The consumption could be interrupted at this step but it exits after the message is processed.
     *
     * @param Context $context
     */
    public function onPostReceived(Context $context);

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
