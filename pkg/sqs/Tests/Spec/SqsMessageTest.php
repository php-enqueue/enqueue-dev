<?php
namespace Enqueue\Sqs\Tests\Spec;

use Enqueue\Psr\Spec\PsrMessageSpec;
use Enqueue\Sqs\SqsMessage;

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