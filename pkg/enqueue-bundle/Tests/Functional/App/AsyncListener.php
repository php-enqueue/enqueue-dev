<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Enqueue\AsyncEventDispatcher\Registry;
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

    private $syncMode = [];

    /**
     * @param ProducerInterface $producer
     * @param Registry          $registry
     */
    public function __construct(ProducerInterface $producer, Registry $registry)
    {
        $this->producer = $producer;
        $this->registry = $registry;
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
    public function onEvent(Event $event = null, $eventName)
    {
        if (false == $this->isSyncMode($eventName)) {
            $transformerName = $this->registry->getTransformerNameForEvent($eventName);

            $psrMessage = $this->registry->getTransformer($transformerName)->toMessage($eventName, $event);
            $message = new Message($psrMessage->getBody());
            $message->setScope(Message::SCOPE_APP);
            $message->setProperty('event_name', $eventName);
            $message->setProperty('transformer_name', $transformerName);

            $this->producer->sendCommand('symfony_events', $message);
        }
    }
}
