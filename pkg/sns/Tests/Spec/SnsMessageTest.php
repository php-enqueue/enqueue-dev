<?php

namespace Enqueue\Sns\Tests\Spec;

use Enqueue\Sns\SnsMessage;
use Interop\Queue\Spec\MessageSpec;

class SnsMessageTest extends MessageSpec
{
    protected function createMessage()
    {
        return new SnsMessage();
    }
}
