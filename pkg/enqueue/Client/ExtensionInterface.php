<?php

namespace Enqueue\Client;

interface ExtensionInterface
{
    /**
     * @param OnPrepareMessage $context
     */
    public function onPrepareMessage(OnPrepareMessage $context);

    /**
     * @param OnSend $context
     */
    public function onSend(OnSend $context);

    /**
     * @param OnPostSend $context
     */
    public function onPostSend(OnPostSend $context);
}
