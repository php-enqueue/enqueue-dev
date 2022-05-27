<?php

namespace Enqueue\NullTransporter\Tests\Spec;

use Enqueue\NullTransporter\NullMessage;
use Interop\Queue\Spec\MessageSpec;

class NullMessageTest extends MessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new NullMessage();
    }
}
