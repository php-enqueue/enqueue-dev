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

    /**
     * @param GpsContext $context
     */
    public function __construct(GpsContext $context)
    {
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     */
    public function send(PsrDestination $destination, PsrMessage $message)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, GpsTopic::class);
        InvalidMessageException::assertMessageInstanceOf($message, GpsMessage::class);

        /** @var Topic $topic */
        $topic = $this->context->getClient()->topic($destination->getTopicName());
        $topic->publish([
            'data' => json_encode($message),
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDeliveryDelay($deliveryDelay)
    {
        if (null === $deliveryDelay) {
            return;
        }

        throw DeliveryDelayNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryDelay()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority($priority)
    {
        if (null === $priority) {
            return;
        }

        throw PriorityNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
    }

    /**
     * {@inheritdoc}
     */
    public function setTimeToLive($timeToLive)
    {
        if (null === $timeToLive) {
            return;
        }

        throw TimeToLiveNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeToLive()
    {
    }
}
