<?php

namespace Enqueue\Tests\Mocks;

use Enqueue\Client\EmptyExtensionTrait;
use Enqueue\Client\ExtensionInterface;
use Enqueue\Client\PreSend;

class CustomPrepareBodyClientExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    public function onPreSendCommand(PreSend $context): void
    {
        $context->getMessage()->setBody('theCommandBodySerializedByCustomExtension');
    }

    public function onPreSendEvent(PreSend $context): void
    {
        $context->getMessage()->setBody('theEventBodySerializedByCustomExtension');
    }
}
