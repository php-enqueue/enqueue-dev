<?php

namespace Enqueue\Psr\Tests;

use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrMessageTrait;

class PsrMessageTraitTest extends BasePsrMessageTest
{
    /**
     * @return PsrMessage
     */
    protected function createMessage()
    {
        return new TestMessage();
    }
}

class TestMessage implements PsrMessage
{
    use PsrMessageTrait;

    public function setCorrelationId($correlationId)
    {
    }

    public function getCorrelationId()
    {
    }

    public function setMessageId($messageId)
    {
    }

    public function getMessageId()
    {
    }

    public function getTimestamp()
    {
    }

    public function setTimestamp($timestamp)
    {
    }

    public function setReplyTo($replyTo)
    {
    }

    public function getReplyTo()
    {
    }
}
