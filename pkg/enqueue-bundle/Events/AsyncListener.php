<?php

namespace Enqueue\Bundle\Events;

use Enqueue\Client\Message;
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
     * @param Event  $event
     * @param string $eventName
     */
    public function onEvent(Event $event = null, $eventName)
    {
        if (false == isset($this->syncMode[$eventName])) {
            $transformerName = $this->registry->getTransformerNameForEvent($eventName);

            $message = $this->registry->getTransformer($transformerName)->toMessage($eventName, $event);
            $message->setScope(Message::SCOPE_APP);
            $message->setProperty('event_name', $eventName);
            $message->setProperty('transformer_name', $transformerName);

            $this->producer->send('event.'.$eventName, $message);
        }
    }
}
