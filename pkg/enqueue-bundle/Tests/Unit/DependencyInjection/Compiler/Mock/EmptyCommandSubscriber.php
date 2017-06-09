<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler\Mock;

use Enqueue\Client\CommandSubscriberInterface;

class EmptyCommandSubscriber implements CommandSubscriberInterface
{
    public static function getSubscribedCommand()
    {
        return '';
    }
}
