<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\LegacyEventDispatcherProxy;
use Symfony\Contracts\EventDispatcher\Event as ContractEvent;

if (class_exists(Event::class) && !class_exists(LegacyEventDispatcherProxy::class)) {
    /**
     * Symfony < 4.3.
     */
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
} elseif (class_exists(Event::class)) {
    /**
     * Symfony >= 4.3 and < 5.0.
     */
    class AsyncListener extends AbstractAsyncListener
    {
        /**
         * @param Event|ContractEvent $event
         * @param string              $eventName
         */
        public function onEvent($event, $eventName)
        {
            $this->onEventInternal($event, $eventName);
        }
    }
} else {
    /**
     * Symfony >= 5.0.
     */
    class AsyncListener extends AbstractAsyncListener
    {
        /**
         * @param string $eventName
         */
        public function onEvent(ContractEvent $event, $eventName)
        {
            $this->onEventInternal($event, $eventName);
        }
    }
}
