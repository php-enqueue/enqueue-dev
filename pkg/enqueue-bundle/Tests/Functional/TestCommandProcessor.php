<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Client\CommandSubscriberInterface;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class TestCommandProcessor implements PsrProcessor, CommandSubscriberInterface
{
    const COMMAND = 'test-command';

    /**
     * @var PsrMessage
     */
    public $message;

    public function process(PsrMessage $message, PsrContext $context)
    {
        $this->message = $message;

        return self::ACK;
    }

    public static function getSubscribedCommand()
    {
        return self::COMMAND;
    }
}
