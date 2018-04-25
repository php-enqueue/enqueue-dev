<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class TestCommandSubscriberProcessor implements PsrProcessor, CommandSubscriberInterface
{
    public $calls = [];

    public function process(PsrMessage $message, PsrContext $context)
    {
        $this->calls[] = $message;

        return Result::reply(
            $context->createMessage($message->getBody().'Reply')
        );
    }

    public static function getSubscribedCommand()
    {
        return 'test_command_subscriber';
    }
}
