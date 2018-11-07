<?php

namespace Enqueue\Client;

interface DriverPreSendExtensionInterface
{
    public function onDriverPreSend(DriverPreSend $context): void;
}
