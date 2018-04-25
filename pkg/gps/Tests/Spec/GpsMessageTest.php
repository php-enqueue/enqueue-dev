<?php

namespace Enqueue\Gps\Tests\Spec;

use Enqueue\Gps\GpsMessage;
use Interop\Queue\Spec\PsrMessageSpec;

class GpsMessageTest extends PsrMessageSpec
{
    protected function createMessage()
    {
        return new GpsMessage();
    }
}
