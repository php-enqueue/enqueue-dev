<?php

namespace Enqueue\AsyncEventDispatcher;

use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AsyncProcessor implements PsrProcessor
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var AsyncEventDispatcher|OldAsyncEventDispatcher
     */
    private $dispatcher;

    /**
     * @param Registry                 $registry
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(Registry $registry, EventDispatcherInterface $dispatcher)
    {
        $this->registry = $registry;

        if (false == ($dispatcher instanceof AsyncEventDispatcher || $dispatcher instanceof OldAsyncEventDispatcher)) {
            throw new \InvalidArgumentException(sprintf(
                'The dispatcher argument must be either instance of "%s" or "%s" but got "%s"',
                AsyncEventDispatcher::class,
                OldAsyncEventDispatcher::class,
                get_class($dispatcher)
            ));
        }

        $this->dispatcher = $dispatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $message, PsrContext $context)
    {
        if (false == $eventName = $message->getProperty('event_name')) {
            return Result::reject('The message is missing "event_name" property');
        }
        if (false == $transformerName = $message->getProperty('transformer_name')) {
            return Result::reject('The message is missing "transformer_name" property');
        }

        $event = $this->registry->getTransformer($transformerName)->toEvent($eventName, $message);

        $this->dispatcher->dispatchAsyncListenersOnly($eventName, $event);

        return self::ACK;
    }
}
