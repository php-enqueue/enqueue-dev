<?php

declare(strict_types=1);

namespace Enqueue\RdKafka;

use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\PurgeQueueNotSupportedException;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Producer as VendorProducer;
use RdKafka\TopicConf;

class RdKafkaContext implements Context
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
     * @var RdKafkaConsumer[]
     */
    private $rdKafkaConsumers;

    /**
     * @var ConsumeMessageTransformer
     */
    private $consumeMessageTransformer;

    /**
     * @var ProduceMessageTransformer
     */
    private $produceMessageTransformer;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $this->kafkaConsumers = [];
        $this->rdKafkaConsumers = [];

        $this->setSerializer(new JsonSerializer());

        $messageTransformer = new NullMessageTransformer();

        $this->consumeMessageTransformer = $messageTransformer;
        $this->produceMessageTransformer = $messageTransformer;
    }

    /**
     * @return RdKafkaMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        return new RdKafkaMessage($body, $properties, $headers);
    }

    /**
     * @return RdKafkaTopic
     */
    public function createTopic(string $topicName): Topic
    {
        return new RdKafkaTopic($topicName);
    }

    /**
     * @return RdKafkaTopic
     */
    public function createQueue(string $queueName): Queue
    {
        return new RdKafkaTopic($queueName);
    }

    public function createTemporaryQueue(): Queue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return RdKafkaProducer
     */
    public function createProducer(): Producer
    {
        $producer = new RdKafkaProducer($this->getProducer(), $this->getSerializer());

        $producer->setMessageTransformer($this->produceMessageTransformer);

        return $producer;
    }

    /**
     * @param RdKafkaTopic $destination
     *
     * @return RdKafkaConsumer
     */
    public function createConsumer(Destination $destination): Consumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, RdKafkaTopic::class);

        $queueName = $destination->getQueueName();

        if (!isset($this->rdKafkaConsumers[$queueName])) {
            $this->kafkaConsumers[] = $kafkaConsumer = new KafkaConsumer($this->getConf());

            $consumer = new RdKafkaConsumer(
                $kafkaConsumer,
                $this,
                $destination,
                $this->getSerializer()
            );

            $consumer->setMessageTransformer($this->consumeMessageTransformer);

            if (isset($this->config['commit_async'])) {
                $consumer->setCommitAsync($this->config['commit_async']);
            }

            $this->rdKafkaConsumers[$queueName] = $consumer;
        }

        return $this->rdKafkaConsumers[$queueName];
    }

    public function close(): void
    {
        $kafkaConsumers = $this->kafkaConsumers;
        $this->kafkaConsumers = [];
        $this->rdKafkaConsumers = [];

        foreach ($kafkaConsumers as $kafkaConsumer) {
            $kafkaConsumer->unsubscribe();
        }
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    public function purgeQueue(Queue $queue): void
    {
        throw PurgeQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public static function getLibrdKafkaVersion(): string
    {
        if (!defined('RD_KAFKA_VERSION')) {
            throw new \RuntimeException('RD_KAFKA_VERSION constant is not defined. Phprdkafka is probably not installed');
        }
        $major = (RD_KAFKA_VERSION & 0xFF000000) >> 24;
        $minor = (RD_KAFKA_VERSION & 0x00FF0000) >> 16;
        $patch = (RD_KAFKA_VERSION & 0x0000FF00) >> 8;

        return "$major.$minor.$patch";
    }

    private function getProducer(): VendorProducer
    {
        if (null === $this->producer) {
            $this->producer = new VendorProducer($this->getConf());

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

            if (isset($this->config['stats_cb'])) {
                $this->conf->setStatsCb($this->config['stats_cb']);
            }

            $this->conf->setDefaultTopicConf($topicConf);
        }

        return $this->conf;
    }
}
