<?php

namespace Enqueue\AsyncEventDispatcher;

use Interop\Queue\Context;
use Interop\Queue\Queue;
use Symfony\Component\EventDispatcher\Event;

abstract class AbstractAsyncListener
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var Queue
     */
    protected $eventQueue;

    /**
     * @var bool
     */
    protected $syncMode;

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
     * @param string $eventName
     *
     * @return bool
     */
    public function isSyncMode($eventName)
    {
        return isset($this->syncMode[$eventName]);
    }
}
