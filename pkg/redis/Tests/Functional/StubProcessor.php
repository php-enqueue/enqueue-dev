<?php
namespace Enqueue\Redis\Tests\Functional;

use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;

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
