<?php

namespace Enqueue\Bundle\Events;

use Enqueue\Client\Message;
use Enqueue\Psr\PsrMessage;
use Symfony\Component\EventDispatcher\Event;

class PhpSerializerEventTransformer implements EventTransformer
{
    /**
     * {@inheritdoc}
     */
    public function toMessage($eventName, Event $event = null)
    {
        $message = new Message();
        $message->setBody(serialize($event));

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function toEvent($eventName, PsrMessage $message)
    {
        return unserialize($message->getBody());
    }
}
