<?php

namespace Enqueue\Null\Tests\Spec;

use Enqueue\Null\NullMessage;
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
