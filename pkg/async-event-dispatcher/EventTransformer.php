<?php

namespace Enqueue\AsyncEventDispatcher;

use Interop\Queue\Message;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\Event as ContractEvent;

if (class_exists(Event::class)) {
    /**
     * Symfony < 5.0.
     */
    interface EventTransformer
    {
        /**
         * @param string                   $eventName
         * @param ContractEvent|Event|null $event
         *
         * @return Message
         */
        public function toMessage($eventName, $event = null);

        /**
         * If you able to transform message back to event return it.
         * If you failed to transform for some reason you can return a string status.
         *
         * @param mixed $eventName
         *
         * @return ContractEvent|Event|string|object
         *
         * @see Process constants) or an object that implements __toString method.
         *      The object must have a __toString method is supposed to be used as Processor::process return value.
         */
        public function toEvent($eventName, Message $message);
    }
} else {
    /**
     * Symfony >= 5.0.
     */
    interface EventTransformer
    {
        /**
         * @param string $eventName
         *
         * @return Message
         */
        public function toMessage($eventName, ContractEvent $event = null);

        /**
         * If you able to transform message back to event return it.
         * If you failed to transform for some reason you can return a string status.
         *
         * @param mixed $eventNAme
         * @param mixed $eventName
         *
         * @return ContractEvent|string|object
         *
         * @see Process constants) or an object that implements __toString method.
         *      The object must have a __toString method is supposed to be used as Processor::process return value.
         */
        public function toEvent($eventName, Message $message);
    }
}
