<?php

namespace Enqueue\Tests\Mocks;

use Enqueue\Client\PreSend;
use Enqueue\Client\PreSendCommandExtensionInterface;
use Enqueue\Client\PreSendEventExtensionInterface;

class CustomPrepareBodyClientExtension implements PreSendEventExtensionInterface, PreSendCommandExtensionInterface
{
    public function onPreSendCommand(PreSend $context): void
    {
        $context->getMessage()->setBody('theCommandBodySerializedByCustomExtension');
    }

    public function onPreSendEvent(PreSend $context): void
    {
        $context->getMessage()->setBody('theEventBodySerializedByCustomExtension');
    }
}
