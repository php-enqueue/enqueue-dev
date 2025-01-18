<?php

namespace Enqueue\Fs\Tests\Spec;

use Enqueue\Fs\FsMessage;
use Interop\Queue\Spec\MessageSpec;

class FsMessageTest extends MessageSpec
{
    protected function createMessage()
    {
        return new FsMessage();
    }
}
