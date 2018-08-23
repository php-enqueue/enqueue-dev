<?php

namespace Enqueue\Gps;

use Google\Cloud\PubSub\Topic;
use Interop\Queue\DeliveryDelayNotSupportedException;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PriorityNotSupportedException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\TimeToLiveNotSupportedException;

class GpsProducer implements PsrProducer
{
    /**
     * @var GpsContext
     */
    private $context;

    public function __construct(GpsContext $context)
    {
        $this->context = $context;
    }

    /**
     * @param GpsTopic   $destination
     * @param GpsMessage $message
     */
    public function send(PsrDestination $destination, PsrMessage $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, GpsTopic::class);
        InvalidMessageException::assertMessageInstanceOf($message, GpsMessage::class);

        /** @var Topic $topic */
        $topic = $this->context->getClient()->topic($destination->getTopicName());
        $topic->publish([
            'data' => json_encode($message),
        ]);
    }

    public function setDeliveryDelay(int $deliveryDelay = null): PsrProducer
    {
        if (null === $deliveryDelay) {
            return $this;
        }

        throw DeliveryDelayNotSupportedException::providerDoestNotSupportIt();
    }

    public function getDeliveryDelay(): ?int
    {
        return null;
    }

    public function setPriority(int $priority = null): PsrProducer
    {
        if (null === $priority) {
            return $this;
        }

        throw PriorityNotSupportedException::providerDoestNotSupportIt();
    }

    public function getPriority(): ?int
    {
        return null;
    }

    public function setTimeToLive(int $timeToLive = null): PsrProducer
    {
        if (null === $timeToLive) {
            return $this;
        }

        throw TimeToLiveNotSupportedException::providerDoestNotSupportIt();
    }

    public function getTimeToLive(): ?int
    {
        return null;
    }
}
