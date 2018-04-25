<?php

namespace Enqueue\Consumption;

interface ExtensionInterface
{
    /**
     * Executed only once at the very begining of the consumption.
     * At this stage the context does not contain processor, consumer and queue.
     *
     * @param Context $context
     */
    public function onStart(Context $context);

    /**
     * Executed at every new cycle before we asked a broker for a new message.
     * At this stage the context already contains processor, consumer and queue.
     * The consumption could be interrupted at this step.
     *
     * @param Context $context
     */
    public function onBeforeReceive(Context $context);

    /**
     * Executed when a new message is received from a broker but before it was passed to processor
     * The context contains a message.
     * The extension may set a status. If the status is set the exception is thrown
     * The consumption could be interrupted at this step but it exits after the message is processed.
     *
     * @param Context $context
     */
    public function onPreReceived(Context $context);

    /**
     * Executed when a message is processed by a processor or a result was set in onPreReceived method.
     * BUT before the message status was sent to the broker
     * The consumption could be interrupted at this step but it exits after the message is processed.
     *
     * @param Context $context
     */
    public function onResult(Context $context);

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
