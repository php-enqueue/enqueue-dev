<?php

namespace Enqueue\Gps\Tests\Spec;

use Enqueue\Gps\GpsMessage;
use Interop\Queue\Spec\MessageSpec;

class GpsMessageTest extends MessageSpec
{
    protected function createMessage()
    {
        return new GpsMessage();
    }
}
