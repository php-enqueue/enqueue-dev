<?php

namespace Enqueue\Client;

interface PreSendEventExtensionInterface
{
    /**
     * @throws \Exception
     */
    public function onPreSendEvent(PreSend $context): void;
}
