<?php

namespace Enqueue\Bundle\Tests\Functional;

use Enqueue\Client\CommandSubscriberInterface;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;

class TestCommandProcessor implements Processor, CommandSubscriberInterface
{
    public const COMMAND = 'test-command';

    /**
     * @var Message
     */
    public $message;

    public function process(Message $message, Context $context)
    {
        $this->message = $message;

        return self::ACK;
    }

    public static function getSubscribedCommand()
    {
        return self::COMMAND;
    }
}
