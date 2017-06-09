<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock;

use Enqueue\Client\CommandSubscriberInterface;

class ProcessorNameCommandSubscriber implements CommandSubscriberInterface
{
    public static function getSubscribedCommand()
    {
        return [
            'processorName' => 'the-command-name',
            'queueName' => 'the-command-queue-name',
        ];
    }
}
