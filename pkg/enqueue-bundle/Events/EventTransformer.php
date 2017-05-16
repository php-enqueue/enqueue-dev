<?php

namespace Enqueue\Bundle\Events;

use Enqueue\Client\Message;
use Enqueue\Psr\PsrMessage;
use Symfony\Component\EventDispatcher\Event;

interface EventTransformer
{
    /**
     * @param string     $eventName
     * @param Event|null $event
     *
     * @return Message
     */
    public function toMessage($eventName, Event $event = null);

    /**
     * @param string     $eventName
     * @param PsrMessage $message
     *
     * @return Event|null
     */
    public function toEvent($eventName, PsrMessage $message);
}
