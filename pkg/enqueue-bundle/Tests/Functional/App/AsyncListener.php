<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Symfony\Contracts\EventDispatcher\Event;

class AsyncListener extends AbstractAsyncListener
{
    /**
     * @param string $eventName
     */
    public function onEvent(Event $event, $eventName)
    {
        $this->onEventInternal($event, $eventName);
    }
}
