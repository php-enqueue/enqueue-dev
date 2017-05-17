<?php

namespace Enqueue\Bundle\Events;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class ProxyEventDispatcher extends EventDispatcher
{
    /**
     * @var EventDispatcherInterface
     */
    private $trueEventDispatcher;

    /**
     * @var AsyncListener
     */
    private $asyncListener;

    /**
     * @param EventDispatcherInterface $trueEventDispatcher
     * @param AsyncListener            $asyncListener
     */
    public function __construct(EventDispatcherInterface $trueEventDispatcher, AsyncListener $asyncListener)
    {
        $this->trueEventDispatcher = $trueEventDispatcher;
        $this->asyncListener = $asyncListener;
    }

    public function resetSyncMode()
    {
        $this->asyncListener->resetSyncMode();
    }

    /**
     * @param string $eventName
     */
    public function syncMode($eventName)
    {
        $this->asyncListener->syncMode($eventName);
    }

    /**
     * {@inheritdoc}
     */
    public function dispatch($eventName, Event $event = null)
    {
        parent::dispatch($eventName, $event);

        $this->trueEventDispatcher->dispatch($eventName, $event);
    }
}
