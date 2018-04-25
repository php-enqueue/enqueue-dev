<?php

namespace Enqueue\AsyncEventDispatcher;

use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpKernel\Kernel;

class PhpSerializerEventTransformer implements EventTransformer
{
    /**
     * @var PsrContext
     */
    private $context;

    /**
     * @var bool
     */
    private $skipSymfonyVersionCheck;

    /**
     * @param PsrContext $context
     * @param bool       $skipSymfonyVersionCheck It is useful when async dispatcher is used without Kernel. So there is no way to check the version.
     */
    public function __construct(PsrContext $context, $skipSymfonyVersionCheck = false)
    {
        $this->context = $context;
        $this->skipSymfonyVersionCheck = $skipSymfonyVersionCheck;
    }

    /**
     * {@inheritdoc}
     */
    public function toMessage($eventName, Event $event = null)
    {
        $this->assertSymfony30OrHigher();

        return $this->context->createMessage(serialize($event));
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
        if ($this->skipSymfonyVersionCheck) {
            return;
        }

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
