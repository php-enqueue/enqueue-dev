<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;

class TestCommandSubscriberProcessor implements Processor, CommandSubscriberInterface
{
    public $calls = [];

    public function process(Message $message, Context $context)
    {
        $this->calls[] = $message;

        return Result::reply(
            $context->createMessage($message->getBody().'Reply')
        );
    }

    public static function getSubscribedCommand()
    {
        return 'theCommand';
    }
}
