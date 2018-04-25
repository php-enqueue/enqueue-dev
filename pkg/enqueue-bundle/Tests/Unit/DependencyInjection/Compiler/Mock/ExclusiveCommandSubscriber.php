<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock;

use Enqueue\Client\CommandSubscriberInterface;

class ExclusiveCommandSubscriber implements CommandSubscriberInterface
{
    public static function getSubscribedCommand()
    {
        return [
            'processorName' => 'the-exclusive-command-name',
            'queueName' => 'the-queue-name',
            'queueNameHardcoded' => true,
            'exclusive' => true,
        ];
    }
}
