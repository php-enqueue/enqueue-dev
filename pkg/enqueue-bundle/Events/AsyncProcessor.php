<?php

namespace Enqueue\Bundle\Events;

use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;

class AsyncProcessor implements PsrProcessor
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
        if (false == $transformerName = $message->getProperty('transformer_name')) {
            return self::REJECT;
        }

        $event = $this->registry->getTransformer($transformerName)->toEvent($eventName, $message);

        $this->eventDispatcher->dispatchAsyncListenersOnly($eventName, $event);

        return self::ACK;
    }
}
