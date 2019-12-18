<?php

namespace Enqueue\AsyncEventDispatcher;

use Interop\Queue\Context;
use Interop\Queue\Message;

abstract class AbstractPhpSerializerEventTransformer
{
    /**
     * @var Context
     */
    protected $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function toEvent($eventName, Message $message)
    {
        return unserialize($message->getBody());
    }
}
