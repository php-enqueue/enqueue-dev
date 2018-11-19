<?php

namespace Enqueue\Client;

interface PreSendCommandExtensionInterface
{
    public function onPreSendCommand(PreSend $context): void;
}
