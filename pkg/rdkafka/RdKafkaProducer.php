<?php

namespace Enqueue\RdKafka;

use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use RdKafka\Producer;

class RdKafkaProducer implements PsrProducer
{
    use SerializerAwareTrait;
    use KeySerializerAwareTrait;

    /**
     * @var Producer
     */
    private $producer;

    /**
     * @param Producer           $producer
     * @param Serializer         $serializer
     * @param KeySerializer|null $keySerializer
     */
    public function __construct(Producer $producer, Serializer $serializer, KeySerializer $keySerializer = null)
    {
        $this->producer = $producer;

        if (!$keySerializer) {
            $keySerializer = new NoOpKeySerializer();
        }

        $this->setSerializer($serializer);
        $this->setKeySerializer($keySerializer);
    }

    /**
     * {@inheritdoc}
     *
     * @param RdKafkaTopic   $destination
     * @param RdKafkaMessage $message
     */
    public function send(PsrDestination $destination, PsrMessage $message)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, RdKafkaTopic::class);
        InvalidMessageException::assertMessageInstanceOf($message, RdKafkaMessage::class);

        $partition = $message->getPartition() ?: $destination->getPartition() ?: RD_KAFKA_PARTITION_UA;
        $key = $message->getKey() ?: $destination->getKey() ?: null;

        if (null !== $key) {
            $key = $this->keySerializer->toString($key);
        }

        $payload = $this->serializer->toString($message);

        $topic = $this->producer->newTopic($destination->getTopicName(), $destination->getConf());
        $topic->produce($partition, 0 /* must be 0 */, $payload, $key);
    }

    /**
     * {@inheritdoc}
     */
    public function setDeliveryDelay($deliveryDelay)
    {
        if (null === $deliveryDelay) {
            return;
        }

        throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryDelay()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority($priority)
    {
        if (null === $priority) {
            return;
        }

        throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setTimeToLive($timeToLive)
    {
        if (null === $timeToLive) {
            return;
        }

        throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeToLive()
    {
        return null;
    }
}
