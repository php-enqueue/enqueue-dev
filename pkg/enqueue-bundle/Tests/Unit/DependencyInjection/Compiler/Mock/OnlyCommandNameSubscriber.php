<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock;

use Enqueue\Client\CommandSubscriberInterface;

class OnlyCommandNameSubscriber implements CommandSubscriberInterface
{
    public static function getSubscribedCommand()
    {
        return 'the-command-name';
    }
}
