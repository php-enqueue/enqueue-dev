<?php

namespace Enqueue\Bundle\Tests\Functional\App;

use Enqueue\Bundle\Events\EventTransformer;
use Enqueue\Client\Message;
use Enqueue\Psr\PsrMessage;
use Enqueue\Util\JSON;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\EventDispatcher\GenericEvent;

class TestAsyncEventTransformer implements EventTransformer
{
    public function toMessage($eventName, Event $event = null)
    {
        /** @var GenericEvent $event */
        if (false == $event instanceof GenericEvent) {
            throw new \LogicException('Must be GenericEvent');
        }

        $message = new Message();
        $message->setBody([
            'subject' => $event->getSubject(),
            'arguments' => $event->getArguments(),
        ]);

        return $message;
    }

    public function toEvent($eventName, PsrMessage $message)
    {
        $data = JSON::decode($message->getBody());

        return new GenericEvent($data['subject'], $data['arguments']);
    }
}
