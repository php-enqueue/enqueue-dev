<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\Event as ContractEvent;

if (class_exists(Event::class)) {
    /**
     * Symfony < 5.0.
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
