<?php

namespace Enqueue\Bundle\Events;

use Enqueue\Client\Message;
use Enqueue\Psr\PsrMessage;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Kernel;

class PhpSerializerEventTransformer implements EventTransformer
{
    /**
     * {@inheritdoc}
     */
    public function toMessage($eventName, Event $event = null)
    {
        $this->assertSymfony30OrHigher();

        $message = new Message();
        $message->setBody(serialize($event));

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function toEvent($eventName, PsrMessage $message)
    {
        $this->assertSymfony30OrHigher();

        return unserialize($message->getBody());
    }

    private function assertSymfony30OrHigher()
    {
        if (version_compare(Kernel::VERSION, '3.0', '<')) {
            throw new \LogicException(
                'This transformer does not work on Symfony prior 3.0. '.
                'The event contains eventDispatcher and therefor could not be serialized. '.
                'You have to register a transformer for every async event. '.
                'Read the doc: https://github.com/php-enqueue/enqueue-dev/blob/master/docs/bundle/async_events.md#event-transformer'
            );
        }
    }
}
