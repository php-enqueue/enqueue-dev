<?php

namespace Enqueue\Client;

interface PreSendCommandExtensionInterface
{
    /**
     * @throws \Exception
     */
    public function onPreSendCommand(PreSend $context): void;
}
