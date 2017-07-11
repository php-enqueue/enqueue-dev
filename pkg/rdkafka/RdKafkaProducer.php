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
    /**
     * @var Producer
     */
    private $producer;

    /**
     * @param Producer $producer
     */
    public function __construct(Producer $producer)
    {
        $this->producer = $producer;
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
        $payload = json_encode($message);

        $topic = $this->producer->newTopic($destination->getTopicName(), $destination->getConf());
        $topic->produce($partition, 0 /* must be 0 */, $payload, $key);
    }
}
