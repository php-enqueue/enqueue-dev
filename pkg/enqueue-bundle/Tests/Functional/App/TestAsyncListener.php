<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class TestAsyncListener
{
    public $calls = [];

    public function onEvent(Event $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        $this->calls[] = func_get_args();
    }
}
