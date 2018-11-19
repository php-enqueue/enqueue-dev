<?php

namespace Enqueue\Redis\Tests\Functional;

use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;

class StubProcessor implements Processor
{
    public $result = self::ACK;

    /** @var Message */
    public $lastProcessedMessage;

    public function process(Message $message, Context $context)
    {
        $this->lastProcessedMessage = $message;

        return $this->result;
    }
}
