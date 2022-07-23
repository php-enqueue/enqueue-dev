<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Exception\PriorityNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use RdKafka\Producer as VendorProducer;

class RdKafkaProducer implements Producer
{
    use SerializerAwareTrait;

    /**
     * @var VendorProducer
     */
    private $producer;

    public function __construct(VendorProducer $producer, Serializer $serializer)
    {
        $this->producer = $producer;

        $this->setSerializer($serializer);
    }

    /**
     * @param RdKafkaTopic   $destination
     * @param RdKafkaMessage $message
     */
    public function send(Destination $destination, Message $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, RdKafkaTopic::class);
        InvalidMessageException::assertMessageInstanceOf($message, RdKafkaMessage::class);

        $partition = $message->getPartition() ?? $destination->getPartition() ?? RD_KAFKA_PARTITION_UA;
        $payload = $this->serializer->toString($message);
        $key = $message->getKey() ?? $destination->getKey() ?? null;

        $topic = $this->producer->newTopic($destination->getTopicName(), $destination->getConf());

        // Note: Topic::producev method exists in phprdkafka > 3.1.0
        // Headers in payload are maintained for backwards compatibility with apps that might run on lower phprdkafka version
        if (method_exists($topic, 'producev')) {
            // Phprdkafka <= 3.1.0 will fail calling `producev` on librdkafka >= 1.0.0 causing segfault
            // Since we are forcing to use at least librdkafka:1.0.0, no need to check the lib version anymore
            if (false !== phpversion('rdkafka')
                && version_compare(phpversion('rdkafka'), '3.1.0', '<=')) {
                trigger_error(
                    'Phprdkafka <= 3.1.0 is incompatible with librdkafka 1.0.0 when calling `producev`. '.
                    'Falling back to `produce` (without message headers) instead.',
                    \E_USER_WARNING
                );
            } else {
                $topic->producev($partition, 0 /* must be 0 */ , $payload, $key, $message->getHeaders());
                $this->producer->poll(0);

                return;
            }
        }

        $topic->produce($partition, 0 /* must be 0 */ , $payload, $key);
        $this->producer->poll(0);
    }

    /**
     * @return RdKafkaProducer
     */
    public function setDeliveryDelay(int $deliveryDelay = null): Producer
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

        throw new \LogicException('Not implemented');
    }

    public function getTimeToLive(): ?int
    {
        return null;
    }

    public function flush(int $timeout): void
    {
        // Flush method is exposed in phprdkafka 4.0
        if (method_exists($this->producer, 'flush')) {
            $this->producer->flush($timeout);
        }
    }
}
