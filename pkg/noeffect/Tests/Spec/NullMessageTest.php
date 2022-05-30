<?php

namespace Enqueue\NoEffect\Tests\Spec;

use Enqueue\NoEffect\NullMessage;
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
