<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock;

use Enqueue\Client\CommandSubscriberInterface;

class InvalidCommandSubscriber implements CommandSubscriberInterface
{
    public static function getSubscribedCommand()
    {
        return 12345;
    }
}
