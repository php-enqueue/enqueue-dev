<?php

namespace Enqueue\Null\Tests\Spec;

use Enqueue\Null\NullMessage;
use Interop\Queue\Spec\PsrMessageSpec;

class NullMessageTest extends PsrMessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new NullMessage();
    }
}
