<?php

namespace Enqueue\RdKafka;

use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrSubscriptionConsumer;
use Interop\Queue\PsrTopic;
use Interop\Queue\PurgeQueueNotSupportedException;
use Interop\Queue\SubscriptionConsumerNotSupportedException;
use Interop\Queue\TemporaryQueueNotSupportedException;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Producer;
use RdKafka\TopicConf;

class RdKafkaContext implements PsrContext
{
    use SerializerAwareTrait;

    /**
     * @var array
     */
    private $config;

    /**
     * @var Conf
     */
    private $conf;

    /**
     * @var Producer
     */
    private $producer;

    /**
     * @var KafkaConsumer[]
     */
    private $kafkaConsumers;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->kafkaConsumers = [];

        $this->setSerializer(new JsonSerializer());
    }

    /**
     * @return RdKafkaMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): PsrMessage
    {
        return new RdKafkaMessage($body, $properties, $headers);
    }

    /**
     * @return RdKafkaTopic
     */
    public function createTopic(string $topicName): PsrTopic
    {
        return new RdKafkaTopic($topicName);
    }

    /**
     * @return RdKafkaTopic
     */
    public function createQueue(string $queueName): PsrQueue
    {
        return new RdKafkaTopic($queueName);
    }

    public function createTemporaryQueue(): PsrQueue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return RdKafkaProducer
     */
    public function createProducer(): PsrProducer
    {
        return new RdKafkaProducer($this->getProducer(), $this->getSerializer());
    }

    /**
     * @param RdKafkaTopic $destination
     *
     * @return RdKafkaConsumer
     */
    public function createConsumer(PsrDestination $destination): PsrConsumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, RdKafkaTopic::class);

        $this->kafkaConsumers[] = $kafkaConsumer = new KafkaConsumer($this->getConf());

        $consumer = new RdKafkaConsumer(
            $kafkaConsumer,
            $this,
            $destination,
            $this->getSerializer()
        );

        if (isset($this->config['commit_async'])) {
            $consumer->setCommitAsync($this->config['commit_async']);
        }

        return $consumer;
    }

    public function close(): void
    {
        $kafkaConsumers = $this->kafkaConsumers;
        $this->kafkaConsumers = [];

        foreach ($kafkaConsumers as $kafkaConsumer) {
            $kafkaConsumer->unsubscribe();
        }
    }

    public function createSubscriptionConsumer(): PsrSubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    public function purgeQueue(PsrQueue $queue): void
    {
        throw PurgeQueueNotSupportedException::providerDoestNotSupportIt();
    }

    private function getProducer(): Producer
    {
        if (null === $this->producer) {
            $this->producer = new Producer($this->getConf());

            if (isset($this->config['log_level'])) {
                $this->producer->setLogLevel($this->config['log_level']);
            }
        }

        return $this->producer;
    }

    private function getConf(): Conf
    {
        if (null === $this->conf) {
            $topicConf = new TopicConf();

            if (isset($this->config['topic']) && is_array($this->config['topic'])) {
                foreach ($this->config['topic'] as $key => $value) {
                    $topicConf->set($key, $value);
                }
            }

            if (isset($this->config['partitioner'])) {
                $topicConf->setPartitioner($this->config['partitioner']);
            }

            $this->conf = new Conf();

            if (isset($this->config['global']) && is_array($this->config['global'])) {
                foreach ($this->config['global'] as $key => $value) {
                    $this->conf->set($key, $value);
                }
            }

            if (isset($this->config['dr_msg_cb'])) {
                $this->conf->setDrMsgCb($this->config['dr_msg_cb']);
            }

            if (isset($this->config['error_cb'])) {
                $this->conf->setErrorCb($this->config['error_cb']);
            }

            if (isset($this->config['rebalance_cb'])) {
                $this->conf->setRebalanceCb($this->config['rebalance_cb']);
            }

            $this->conf->setDefaultTopicConf($topicConf);
        }

        return $this->conf;
    }
}
