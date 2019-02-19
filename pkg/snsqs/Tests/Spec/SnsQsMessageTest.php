<?php

namespace Enqueue\SnsQs\Tests\Spec;

use Enqueue\SnsQs\SnsQsMessage;
use Interop\Queue\Spec\MessageSpec;

class SnsQsMessageTest extends MessageSpec
{
    protected function createMessage()
    {
        return new SnsQsMessage();
    }
}
