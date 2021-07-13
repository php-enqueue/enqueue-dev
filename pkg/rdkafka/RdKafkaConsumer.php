<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Queue;
use RdKafka\KafkaConsumer;
use RdKafka\TopicPartition;

class RdKafkaConsumer implements Consumer
{
    use SerializerAwareTrait;

    /**
     * @var KafkaConsumer
     */
    private $consumer;

    /**
     * @var RdKafkaContext
     */
    private $context;

    /**
     * @var RdKafkaTopic
     */
    private $topic;

    /**
     * @var bool
     */
    private $subscribed;

    /**
     * @var bool
     */
    private $commitAsync;

    /**
     * @var int|null
     */
    private $offset;

    public function __construct(KafkaConsumer $consumer, RdKafkaContext $context, RdKafkaTopic $topic, Serializer $serializer)
    {
        $this->consumer = $consumer;
        $this->context = $context;
        $this->topic = $topic;
        $this->subscribed = false;
        $this->commitAsync = true;

        $this->setSerializer($serializer);
    }

    public function isCommitAsync(): bool
    {
        return $this->commitAsync;
    }

    public function setCommitAsync(bool $async): void
    {
        $this->commitAsync = $async;
    }

    public function getOffset(): ?int
    {
        return $this->offset;
    }

    public function setOffset(int $offset = null): void
    {
        if ($this->subscribed) {
            throw new \LogicException('The consumer has already subscribed.');
        }

        $this->offset = $offset;
    }

    /**
     * @return RdKafkaTopic
     */
    public function getQueue(): Queue
    {
        return $this->topic;
    }

    /**
     * @return RdKafkaMessage
     */
    public function receive(int $timeout = 0): ?Message
    {
        if (false === $this->subscribed) {
            if (null === $this->offset) {
                $this->consumer->subscribe([$this->getQueue()->getQueueName()]);
            } else {
                $this->consumer->assign([new TopicPartition(
                    $this->getQueue()->getQueueName(),
                    $this->getQueue()->getPartition(),
                    $this->offset
                )]);
            }

            $this->subscribed = true;
        }

        if ($timeout > 0) {
            return $this->doReceive($timeout);
        }

        while (true) {
            if ($message = $this->doReceive(500)) {
                return $message;
            }
        }

        return null;
    }

    /**
     * @return RdKafkaMessage
     */
    public function receiveNoWait(): ?Message
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * @param RdKafkaMessage $message
     */
    public function acknowledge(Message $message): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, RdKafkaMessage::class);

        if (false == $message->getKafkaMessage()) {
            throw new \LogicException('The message could not be acknowledged because it does not have kafka message set.');
        }

        if ($this->isCommitAsync()) {
            $this->consumer->commitAsync($message->getKafkaMessage());
        } else {
            $this->consumer->commit($message->getKafkaMessage());
        }
    }

    /**
     * @param RdKafkaMessage $message
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        $this->acknowledge($message);

        if ($requeue) {
            $this->context->createProducer()->send($this->topic, $message);
        }
    }

    private function doReceive(int $timeout): ?RdKafkaMessage
    {
        $kafkaMessage = $this->consumer->consume($timeout);

        if (null === $kafkaMessage) {
            return null;
        }

        switch ($kafkaMessage->err) {
            case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            case RD_KAFKA_RESP_ERR__TIMED_OUT:
            case RD_KAFKA_RESP_ERR__TRANSPORT:
                return null;
            case RD_KAFKA_RESP_ERR_NO_ERROR:
                $message = $this->serializer->toMessage($kafkaMessage->payload);
                $message->setKey($kafkaMessage->key);
                $message->setPartition($kafkaMessage->partition);
                $message->setKafkaMessage($kafkaMessage);

                // Merge headers passed from Kafka with possible earlier serialized payload headers. Prefer Kafka's.
                // Note: Requires phprdkafka >= 3.1.0
                if (isset($kafkaMessage->headers)) {
                    $message->setHeaders(array_merge($message->getHeaders(), $kafkaMessage->headers));
                }

                return $message;
            default:
                throw new \LogicException($kafkaMessage->errstr(), $kafkaMessage->err);
                break;
        }
    }
}
