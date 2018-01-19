<?php

namespace Enqueue\Client;

trait EmptyExtensionTrait
{
    /**
     * {@inheritdoc}
     */
    public function onPrepareMessage(OnPrepareMessage $context)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onSend(OnSend $context)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSend(OnPostSend $context)
    {
    }
}
