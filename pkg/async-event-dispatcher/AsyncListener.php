<?php

namespace Enqueue\AsyncEventDispatcher;

use Interop\Queue\Context;
use Interop\Queue\Queue;
use Symfony\Component\EventDispatcher\Event;

class AsyncListener
{
    /**
     * @var Context
     */
    private $context;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var Queue
     */
    private $eventQueue;

    /**
     * @var bool
     */
    private $syncMode;

    /**
     * @param Context      $context
     * @param Registry     $registry
     * @param Queue|string $eventQueue
     */
    public function __construct(Context $context, Registry $registry, $eventQueue)
    {
        $this->context = $context;
        $this->registry = $registry;
        $this->eventQueue = $eventQueue instanceof Queue ? $eventQueue : $context->createQueue($eventQueue);
    }

    public function __invoke(Event $event, $eventName)
    {
        $this->onEvent($event, $eventName);
    }

    public function resetSyncMode()
    {
        $this->syncMode = [];
    }

    /**
     * @param string $eventName
     */
    public function syncMode($eventName)
    {
        $this->syncMode[$eventName] = true;
    }

    /**
     * @param $eventName
     */
    public function asyncMode($eventName)
    {
        if($this->isSyncMode($eventName)) {
            unset($this->syncMode[$eventName]);
        }
    }

    /**
     * @param string $eventName
     *
     * @return bool
     */
    public function isSyncMode($eventName)
    {
        return isset($this->syncMode[$eventName]);
    }

    /**
     * @param Event  $event
     * @param string $eventName
     */
    public function onEvent(Event $event, $eventName)
    {
        if (false == isset($this->syncMode[$eventName])) {
            $transformerName = $this->registry->getTransformerNameForEvent($eventName);

            $message = $this->registry->getTransformer($transformerName)->toMessage($eventName, $event);
            $message->setProperty('event_name', $eventName);
            $message->setProperty('transformer_name', $transformerName);

            $this->context->createProducer()->send($this->eventQueue, $message);
        }
    }
}
