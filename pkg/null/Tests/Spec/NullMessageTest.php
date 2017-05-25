<?php

namespace Enqueue\Null\Tests\Spec;

use Enqueue\Null\NullMessage;
use Enqueue\Psr\Spec\PsrMessageSpec;

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
