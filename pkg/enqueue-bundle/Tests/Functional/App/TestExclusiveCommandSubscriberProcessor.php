<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;

class TestExclusiveCommandSubscriberProcessor implements Processor, CommandSubscriberInterface
{
    public $calls = [];

    public function process(Message $message, Context $context)
    {
        $this->calls[] = $message;

        return Result::ACK;
    }

    public static function getSubscribedCommand()
    {
        return [
            'command' => 'theExclusiveCommandName',
            'processor' => 'theExclusiveCommandName',
            'queue' => 'the_exclusive_command_queue',
            'prefix_queue' => true,
            'exclusive' => true,
        ];
    }
}
