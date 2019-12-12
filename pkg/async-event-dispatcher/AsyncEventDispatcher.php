<?php

namespace Enqueue\AsyncEventDispatcher;

use Symfony\Component\EventDispatcher\Event;

if (class_exists(Event::class)) {
    /**
     * Symfony < 5.0.
     */
    class AsyncEventDispatcher extends AbstractAsyncEventDispatcher
    {
        /**
         * {@inheritdoc}
         */
        public function dispatch($event, $eventName = null)
        {
            $this->parentDispatch($event, $eventName);

            return $this->trueEventDispatcher->dispatch($event, $eventName);
        }

        protected function parentDispatch($event, $eventName)
        {
            parent::dispatch($event, $eventName);
        }
    }
} else {
    /**
     * Symfony >= 5.0.
     */
    class AsyncEventDispatcher extends AbstractAsyncEventDispatcher
    {
        /**
         * {@inheritdoc}
         */
        public function dispatch(object $event, string $eventName = null): object
        {
            $this->parentDispatch($event, $eventName);

            return $this->trueEventDispatcher->dispatch($event, $eventName);
        }

        protected function parentDispatch($event, $eventName)
        {
            return parent::dispatch($event, $eventName);
        }
    }
}
