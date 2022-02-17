<?php

namespace Enqueue\AsyncEventDispatcher;

use Interop\Queue\Message;
use Symfony\Contracts\EventDispatcher\Event;

interface EventTransformer
{
    /**
     * @param string $eventName
     *
     * @return Message
     */
    public function toMessage($eventName, Event $event = null);

    /**
     * If you able to transform message back to event return it.
     * If you failed to transform for some reason you can return a string status.
     *
     * @param mixed $eventNAme
     * @param mixed $eventName
     *
     * @return Event|string|object
     *
     * @see Process constants) or an object that implements __toString method.
     *      The object must have a __toString method is supposed to be used as Processor::process return value.
     */
    public function toEvent($eventName, Message $message);
}
