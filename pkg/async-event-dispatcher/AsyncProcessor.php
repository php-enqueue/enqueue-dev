<?php

namespace Enqueue\AsyncEventDispatcher;

use Enqueue\Consumption\Result;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Interop\Queue\Processor;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AsyncProcessor implements Processor
{
    /**
     * @var Registry
     */
    private $registry;

    /**
     * @var AsyncEventDispatcher
     */
    private $dispatcher;

    public function __construct(Registry $registry, EventDispatcherInterface $dispatcher)
    {
        $this->registry = $registry;

        if (false == $dispatcher instanceof AsyncEventDispatcher) {
            throw new \InvalidArgumentException(sprintf(
                'The dispatcher argument must be instance of "%s" but got "%s"',
                AsyncEventDispatcher::class,
                get_class($dispatcher)
            ));
        }

        $this->dispatcher = $dispatcher;
    }

    public function process(Message $message, Context $context)
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
