<?php

namespace Enqueue\Redis\Tests\Functional;

use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class StubProcessor implements PsrProcessor
{
    public $result = self::ACK;

    /** @var PsrMessage */
    public $lastProcessedMessage;

    public function process(PsrMessage $message, PsrContext $context)
    {
        $this->lastProcessedMessage = $message;

        return $this->result;
    }
}
