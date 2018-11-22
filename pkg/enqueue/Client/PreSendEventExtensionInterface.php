<?php

namespace Enqueue\Client;

interface PreSendEventExtensionInterface
{
    public function onPreSendEvent(PreSend $context): void;
}
