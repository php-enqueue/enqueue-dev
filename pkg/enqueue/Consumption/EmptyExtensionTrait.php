<?php

namespace Enqueue\Consumption;

trait EmptyExtensionTrait
{
    /**
     * {@inheritdoc}
     */
    public function onStart(OnStartContext $context)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onBeforeReceive(Context $context)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onResult(Context $context)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onPostReceived(Context $context)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onIdle(Context $context)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onInterrupted(Context $context)
    {
    }
}
