<?php

namespace Enqueue\Bundle\Events;

use Enqueue\Client\ProducerInterface;
use Symfony\Component\EventDispatcher\Event;

class AsyncListener
{
    /**
     * @var ProducerInterface
     */
    private $producer;

    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var bool
     */
    private $syncMode;

    /**
     * @param ProducerInterface $producer
     * @param Registry          $registry
     */
    public function __construct(ProducerInterface $producer, Registry $registry)
    {
        $this->producer = $producer;
        $this->registry = $registry;
    }

    public function syncMode($eventName)
    {
        $this->syncMode[$eventName] = true;
    }

    public function onEvent(Event $event, $eventName)
    {
        if (false == isset($this->syncMode[$eventName])) {
            $message = $this->registry->getTransformer($eventName)->toMessage($eventName, $event);
            $message->setProperty('event_name', $eventName);

            $this->producer->send('symfony_events', $message);
        }
    }
}
