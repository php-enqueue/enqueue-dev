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

    /**
     * @var Producer
     */
    private $producer;

    public function __construct(Producer $producer, Serializer $serializer)
    {
        $this->producer = $producer;

        $this->setSerializer($serializer);
    }

    /**
     * @param RdKafkaTopic   $destination
     * @param RdKafkaMessage $message
     */
    public function send(PsrDestination $destination, PsrMessage $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, RdKafkaTopic::class);
        InvalidMessageException::assertMessageInstanceOf($message, RdKafkaMessage::class);

        $partition = $message->getPartition() ?: $destination->getPartition() ?: RD_KAFKA_PARTITION_UA;
        $payload = $this->serializer->toString($message);
        $key = $message->getKey() ?: $destination->getKey() ?: null;

        $topic = $this->producer->newTopic($destination->getTopicName(), $destination->getConf());
        $topic->produce($partition, 0 /* must be 0 */, $payload, $key);
    }

    /**
     * @return RdKafkaProducer
     */
    public function setDeliveryDelay(int $deliveryDelay = null): PsrProducer
    {
        if (null === $deliveryDelay) {
            return $this;
        }

        throw new \LogicException('Not implemented');
    }

    public function getDeliveryDelay(): ?int
    {
        return null;
    }

    /**
     * @return RdKafkaProducer
     */
    public function setPriority(int $priority = null): PsrProducer
    {
        if (null === $priority) {
            return $this;
        }

        throw new \LogicException('Not implemented');
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

        throw new \LogicException('Not implemented');
    }

    public function getTimeToLive(): ?int
    {
        return null;
    }
}
