<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TestAsyncSubscriber implements EventSubscriberInterface
{
    public $calls = [];

    public function onEvent(Event $event, $eventName, EventDispatcherInterface $dispatcher)
    {
        $this->calls[] = func_get_args();
    }

    public static function getSubscribedEvents()
    {
        return ['test_async_subscriber' => 'onEvent'];
    }
}
