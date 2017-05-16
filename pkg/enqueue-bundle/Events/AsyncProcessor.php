<?php

namespace Enqueue\Bundle\Events;

use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;

class AsyncProcessor implements PsrProcessor, TopicSubscriberInterface
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var ProxyEventDispatcher
     */
    private $eventDispatcher;

    /**
     * @param Registry             $registry
     * @param ProxyEventDispatcher $eventDispatcher
     */
    public function __construct(Registry $registry, ProxyEventDispatcher $eventDispatcher)
    {
        $this->registry = $registry;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $message, PsrContext $context)
    {
        if (false == $eventName = $message->getProperty('event_name')) {
            return self::REJECT;
        }

        // TODO set transformer's name explicitly when sending a message.

        $event = $this->registry->getTransformer($eventName)->toEvent($eventName, $message);

        $this->eventDispatcher->syncMode($eventName);
        $this->eventDispatcher->dispatch($eventName, $event);

        return self::ACK;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedTopics()
    {
        return ['symfony_events'];
    }
}
