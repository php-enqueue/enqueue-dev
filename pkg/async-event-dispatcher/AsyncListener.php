<?php

namespace Enqueue\AsyncEventDispatcher;

use Symfony\Contracts\EventDispatcher\Event;

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
