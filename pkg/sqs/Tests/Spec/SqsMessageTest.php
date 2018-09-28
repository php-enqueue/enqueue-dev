<?php

namespace Enqueue\Sqs\Tests\Spec;

use Enqueue\Sqs\SqsMessage;
use Interop\Queue\Spec\MessageSpec;

class SqsMessageTest extends MessageSpec
{
    /**
     * {@inheritdoc}
     */
    protected function createMessage()
    {
        return new SqsMessage();
    }
}
