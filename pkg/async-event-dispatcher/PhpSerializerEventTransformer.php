<?php

namespace Enqueue\AsyncEventDispatcher;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Contracts\EventDispatcher\Event as ContractEvent;

if (class_exists(Event::class)) {
    /**
     * Symfony < 5.0.
     */
    class PhpSerializerEventTransformer extends AbstractPhpSerializerEventTransformer implements EventTransformer
    {
        public function toMessage($eventName, $event = null)
        {
            return $this->context->createMessage(serialize($event));
        }
    }
} else {
    /**
     * Symfony >= 5.0.
     */
    class PhpSerializerEventTransformer extends AbstractPhpSerializerEventTransformer implements EventTransformer
    {
        public function toMessage($eventName, ContractEvent $event = null)
        {
            return $this->context->createMessage(serialize($event));
        }
    }
}
