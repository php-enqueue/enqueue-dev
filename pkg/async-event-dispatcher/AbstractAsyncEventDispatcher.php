<?php

namespace Enqueue\AsyncEventDispatcher;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\EventDispatcher\Event as ContractEvent;

abstract class AbstractAsyncEventDispatcher extends EventDispatcher implements EventDispatcherInterface
{
    /**
     * @var EventDispatcherInterface
     */
    protected $trueEventDispatcher;

    /**
     * @var AsyncListener
     */
    protected $asyncListener;

    /**
     * @param EventDispatcherInterface $trueEventDispatcher
     * @param AsyncListener            $asyncListener
     */
    public function __construct(EventDispatcherInterface $trueEventDispatcher, AsyncListener $asyncListener)
    {
        $this->trueEventDispatcher = $trueEventDispatcher;
        $this->asyncListener = $asyncListener;
    }

    /**
     * This method dispatches only those listeners that were marked as async.
     *
     * @param string                   $eventName
     * @param ContractEvent|Event|null $event
     */
    public function dispatchAsyncListenersOnly($eventName, $event = null)
    {
        try {
            $this->asyncListener->syncMode($eventName);

            $this->parentDispatch($event, $eventName);
        } finally {
            $this->asyncListener->resetSyncMode();
        }
    }

    abstract protected function parentDispatch($event, $eventName);
}
