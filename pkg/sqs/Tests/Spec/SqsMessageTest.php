<?php

namespace Enqueue\Sqs\Tests\Spec;

use Enqueue\Sqs\SqsMessage;
use Interop\Queue\Spec\PsrMessageSpec;

class SqsMessageTest extends PsrMessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new SqsMessage();
    }
}
