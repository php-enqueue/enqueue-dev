<?php

namespace Enqueue\Consumption;

trait EmptyExtensionTrait
{
    /**
     * @param Context $context
     */
    public function onStart(Context $context)
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
