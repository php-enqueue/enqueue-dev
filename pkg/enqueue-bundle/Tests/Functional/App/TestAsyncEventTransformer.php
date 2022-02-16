<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Enqueue\AsyncEventDispatcher\EventTransformer;
use Enqueue\Util\JSON;
use Interop\Queue\Context;
use Interop\Queue\Message;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\Event;

class TestAsyncEventTransformer implements EventTransformer
{
    /**
     * @var Context
     */
    private $context;

    public function __construct(Context $context)
    {
        $this->context = $context;
    }

    public function toMessage($eventName, Event $event = null)
    {
        if (Event::class === get_class($event)) {
            return $this->context->createMessage(json_encode(''));
        }

        /** @var GenericEvent $event */
        if (false == $event instanceof GenericEvent) {
            throw new \LogicException('Must be GenericEvent');
        }

        return $this->context->createMessage(json_encode([
            'subject' => $event->getSubject(),
            'arguments' => $event->getArguments(),
        ]));
    }

    public function toEvent($eventName, Message $message)
    {
        $data = JSON::decode($message->getBody());

        if ('' === $data) {
            return new Event();
        }

        return new GenericEvent($data['subject'], $data['arguments']);
    }
}
