<?php

namespace Enqueue\AsyncEventDispatcher;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\LegacyEventDispatcherProxy;
use Symfony\Contracts\EventDispatcher\Event as ContractEvent;

if (class_exists(Event::class) && !class_exists(LegacyEventDispatcherProxy::class)) {
    /**
     * Symfony < 4.3
     */
    class PhpSerializerEventTransformer extends AbstractPhpSerializerEventTransformer implements EventTransformer
    {
        /**
         * {@inheritdoc}
         */
        public function toMessage($eventName, Event $event = null)
        {
            return $this->context->createMessage(serialize($event));
        }
    }
} elseif (class_exists(Event::class)) {
    /**
     * Symfony >= 4.3 and < 5.0
     */
    class PhpSerializerEventTransformer extends AbstractPhpSerializerEventTransformer implements EventTransformer
    {
        /**
         * {@inheritdoc}
         */
        public function toMessage($eventName, $event = null)
        {
            return $this->context->createMessage(serialize($event));
        }
    }
} else {
    /**
     * Symfony >= 5.0
     */
    class PhpSerializerEventTransformer extends AbstractPhpSerializerEventTransformer implements EventTransformer
    {
        /**
         * {@inheritdoc}
         */
        public function toMessage($eventName, ContractEvent $event = null)
        {
            return $this->context->createMessage(serialize($event));
        }
    }
}
