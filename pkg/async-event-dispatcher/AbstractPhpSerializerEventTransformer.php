<?php

namespace Enqueue\AsyncEventDispatcher;

use Interop\Queue\Context;
use Interop\Queue\Message;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\LegacyEventDispatcherProxy;
use Symfony\Contracts\EventDispatcher\Event as ContractEvent;

abstract class AbstractPhpSerializerEventTransformer
{
    /**
     * @var Context
     */
    protected $context;

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
    public function toEvent($eventName, Message $message)
    {
        return unserialize($message->getBody());
    }
}
