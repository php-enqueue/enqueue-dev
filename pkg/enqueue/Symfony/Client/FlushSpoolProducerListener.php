<?php

namespace Enqueue\Symfony\Client;

use Enqueue\Client\SpoolProducer;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class FlushSpoolProducerListener implements EventSubscriberInterface
{
    /**
     * @var SpoolProducer
     */
    private $producer;

    /**
     * @param SpoolProducer $producer
     */
    public function __construct(SpoolProducer $producer)
    {
        $this->producer = $producer;
    }

    public function flushMessages()
    {
        $this->producer->flush();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events = [];

        if (class_exists(KernelEvents::class)) {
            $events[KernelEvents::TERMINATE] = 'flushMessages';
        }

        if (class_exists(ConsoleEvents::class)) {
            $events[ConsoleEvents::TERMINATE] = 'flushMessages';
        }

        return $events;
    }
}
