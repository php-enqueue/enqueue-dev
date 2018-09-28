<?php

namespace Enqueue\AsyncEventDispatcher;

use Interop\Queue\Context;
use Interop\Queue\Message;
use Symfony\Component\EventDispatcher\Event;

class PhpSerializerEventTransformer implements EventTransformer
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @param Context $context
     */
    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function toMessage($eventName, Event $event = null)
    {
        return $this->context->createMessage(serialize($event));
    }

    /**
     * {@inheritdoc}
     */
    public function toEvent($eventName, Message $message)
    {
        return unserialize($message->getBody());
    }
}
