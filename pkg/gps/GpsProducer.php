<?php

declare(strict_types=1);

namespace Enqueue\Gps;

use Google\Cloud\PubSub\Topic;
use Interop\Queue\Destination;
use Interop\Queue\Exception\DeliveryDelayNotSupportedException;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Exception\PriorityNotSupportedException;
use Interop\Queue\Exception\TimeToLiveNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;

class GpsProducer implements Producer
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
    public function send(Destination $destination, Message $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, GpsTopic::class);
        InvalidMessageException::assertMessageInstanceOf($message, GpsMessage::class);

        /** @var Topic $topic */
        $topic = $this->context->getClient()->topic($destination->getTopicName());
        $topic->publish([
            'data' => json_encode($message),
        ]);
    }

    public function setDeliveryDelay(int $deliveryDelay = null): Producer
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

    public function setPriority(int $priority = null): Producer
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

    public function setTimeToLive(int $timeToLive = null): Producer
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
