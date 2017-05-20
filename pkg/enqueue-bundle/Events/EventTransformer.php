<?php

namespace Enqueue\Bundle\Events;

use Enqueue\Client\Message;
use Enqueue\Consumption\Result;
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
     * If you able to transform message back to event return it.
     * If you failed to transform for some reason you can return instance of Result object ( Like this Result::reject() );.
     *
     * @param string     $eventName
     * @param PsrMessage $message
     *
     * @return Event|Result|null
     */
    public function toEvent($eventName, PsrMessage $message);
}
