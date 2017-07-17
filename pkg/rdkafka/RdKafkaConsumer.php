<?php

namespace Enqueue\RdKafka;

use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrMessage;
use RdKafka\KafkaConsumer;

class RdKafkaConsumer implements PsrConsumer
{
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
     * @param KafkaConsumer  $consumer
     * @param RdKafkaContext $context
     * @param RdKafkaTopic   $topic
     */
    public function __construct(KafkaConsumer $consumer, RdKafkaContext $context, RdKafkaTopic $topic)
    {
        $this->consumer = $consumer;
        $this->context = $context;
        $this->topic = $topic;
        $this->subscribed = false;
        $this->commitAsync = false;
    }

    /**
     * @return bool
     */
    public function isCommitAsync()
    {
        return $this->commitAsync;
    }

    /**
     * @param bool $async
     */
    public function setCommitAsync($async)
    {
        $this->commitAsync = (bool) $async;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueue()
    {
        return $this->topic;
    }

    /**
     * {@inheritdoc}
     */
    public function receive($timeout = 0)
    {
        $this->consumer->subscribe([$this->topic->getTopicName()]);

        $message = null;
        if ($timeout > 0) {
            $message = $this->doReceive($timeout);
        } else {
            while (true) {
                if ($message = $this->doReceive(500)) {
                    break;
                }
            }
        }

        $this->consumer->unsubscribe();

        return $message;
    }

    /**
     * {@inheritdoc}
     */
    public function receiveNoWait()
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     *
     * @param RdKafkaMessage $message
     */
    public function acknowledge(PsrMessage $message)
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
     * {@inheritdoc}
     *
     * @param RdKafkaMessage $message
     */
    public function reject(PsrMessage $message, $requeue = false)
    {
        $this->acknowledge($message);

        if ($requeue) {
            $this->context->createProducer()->send($this->topic, $message);
        }
    }

    /**
     * @param int $timeout
     *
     * @return RdKafkaMessage|null
     */
    private function doReceive($timeout)
    {
        $kafkaMessage = $this->consumer->consume($timeout);

        switch ($kafkaMessage->err) {
            case RD_KAFKA_RESP_ERR__PARTITION_EOF:
            case RD_KAFKA_RESP_ERR__TIMED_OUT:
                break;
            case RD_KAFKA_RESP_ERR_NO_ERROR:
                $message = RdKafkaMessage::jsonUnserialize($kafkaMessage->payload);
                $message->setKey($kafkaMessage->key);
                $message->setPartition($kafkaMessage->partition);
                $message->setKafkaMessage($kafkaMessage);

                return $message;
            default:
                throw new \LogicException($kafkaMessage->errstr(), $kafkaMessage->err);
                break;
        }
    }
}
