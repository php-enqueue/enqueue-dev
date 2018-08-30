<?php

namespace Enqueue\Client;

trait EmptyExtensionTrait
{
    public function onPreSendEvent(PreSend $context): void
    {
    }

    public function onPreSendCommand(PreSend $context): void
    {
    }

    public function onDriverPreSend(DriverPreSend $context): void
    {
    }

    public function onPostSend(PostSend $context): void
    {
    }
}
