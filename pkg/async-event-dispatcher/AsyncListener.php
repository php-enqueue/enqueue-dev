<?php

namespace Enqueue\AsyncEventDispatcher;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\LegacyEventDispatcherProxy;
use Symfony\Contracts\EventDispatcher\Event as ContractEvent;

if (class_exists(Event::class) && !class_exists(LegacyEventDispatcherProxy::class)) {
    /**
     * Symfony < 4.3.
     */
    class AsyncListener extends AbstractAsyncListener
    {
        public function __invoke(Event $event, $eventName)
        {
            $this->onEvent($event, $eventName);
        }

        /**
         * @param string $eventName
         */
        public function onEvent(Event $event, $eventName)
        {
            if (false == isset($this->syncMode[$eventName])) {
                $transformerName = $this->registry->getTransformerNameForEvent($eventName);

                $message = $this->registry->getTransformer($transformerName)->toMessage($eventName, $event);
                $message->setProperty('event_name', $eventName);
                $message->setProperty('transformer_name', $transformerName);

                $this->context->createProducer()->send($this->eventQueue, $message);
            }
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
        public function __invoke($event, $eventName)
        {
            $this->onEvent($event, $eventName);
        }

        /**
         * @param Event|ContractEvent $event
         * @param string              $eventName
         */
        public function onEvent($event, $eventName)
        {
            if (false == isset($this->syncMode[$eventName])) {
                $transformerName = $this->registry->getTransformerNameForEvent($eventName);

                $message = $this->registry->getTransformer($transformerName)->toMessage($eventName, $event);
                $message->setProperty('event_name', $eventName);
                $message->setProperty('transformer_name', $transformerName);

                $this->context->createProducer()->send($this->eventQueue, $message);
            }
        }
    }
} else {
    /**
     * Symfony >= 5.0.
     */
    class AsyncListener extends AbstractAsyncListener
    {
        public function __invoke(ContractEvent $event, $eventName)
        {
            $this->onEvent($event, $eventName);
        }

        /**
         * @param string $eventName
         */
        public function onEvent(ContractEvent $event, $eventName)
        {
            if (false == isset($this->syncMode[$eventName])) {
                $transformerName = $this->registry->getTransformerNameForEvent($eventName);

                $message = $this->registry->getTransformer($transformerName)->toMessage($eventName, $event);
                $message->setProperty('event_name', $eventName);
                $message->setProperty('transformer_name', $transformerName);

                $this->context->createProducer()->send($this->eventQueue, $message);
            }
        }
    }
}
