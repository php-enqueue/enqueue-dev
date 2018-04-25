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
            'processorName' => 'theExclusiveCommandName',
            'queueName' => 'the_exclusive_command_queue',
            'queueNameHardcoded' => true,
            'exclusive' => true,
        ];
    }
}
