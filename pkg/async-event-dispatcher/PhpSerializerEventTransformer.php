<?php

namespace Enqueue\AsyncEventDispatcher;

use Symfony\Contracts\EventDispatcher\Event;

class PhpSerializerEventTransformer extends AbstractPhpSerializerEventTransformer implements EventTransformer
{
    public function toMessage($eventName, ?Event $event = null)
    {
        return $this->context->createMessage(serialize($event));
    }
}
