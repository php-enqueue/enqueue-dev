<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\Context\MessageResult;
use Enqueue\Consumption\Context\PreConsume;
use Enqueue\Consumption\Context\PreSubscribe;
use Enqueue\Consumption\Context\Start;

interface ExtensionInterface
{
    /**
     * Executed only once at the very beginning of the QueueConsumer::consume method call.
     */
    public function onStart(Start $context): void;

    /**
     * The method is called for each BoundProcessor before calling SubscriptionConsumer::subscribe method.
     */
    public function onPreSubscribe(PreSubscribe $context): void;

    /**
     * Executed at every new cycle before calling SubscriptionConsumer::consume method.
     * The consumption could be interrupted at this step.
     */
    public function onPreConsume(PreConsume $context): void;

    /**
     * Executed as soon as a a message is received, before it is passed to a processor
     * The extension may set a result. If the result is set the processor is not called
     * The processor could be changed or decorated at this point.
     */
    public function onMessageReceived(MessageReceived $context): void;

    /**
     * Executed when a message is processed by a processor or a result was set in onMessageReceived extension method.
     * BEFORE the message status was sent to the broker
     * The result could be changed at this point.
     */
    public function onResult(MessageResult $context): void;

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
