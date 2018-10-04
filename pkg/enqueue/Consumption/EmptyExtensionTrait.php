<?php

namespace Enqueue\Consumption;

use Enqueue\Consumption\Context\Start;

trait EmptyExtensionTrait
{
    public function onStart(Start $context): void
    {
    }

    /**
     * @param Context $context
     */
    public function onBeforeReceive(Context $context)
    {
    }

    /**
     * @param Context $context
     */
    public function onPreReceived(Context $context)
    {
    }

    /**
     * @param Context $context
     */
    public function onResult(Context $context)
    {
    }

    /**
     * @param Context $context
     */
    public function onPostReceived(Context $context)
    {
    }

    /**
     * @param Context $context
     */
    public function onIdle(Context $context)
    {
    }

    /**
     * @param Context $context
     */
    public function onInterrupted(Context $context)
    {
    }
}
