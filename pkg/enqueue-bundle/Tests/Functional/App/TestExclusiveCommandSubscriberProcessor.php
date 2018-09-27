<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class TestExclusiveCommandSubscriberProcessor implements PsrProcessor, CommandSubscriberInterface
{
    public $calls = [];

    public function process(PsrMessage $message, PsrContext $context)
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
