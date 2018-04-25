<?php

namespace Enqueue\RdKafka;

use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;
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
     * {@inheritdoc}
     */
    public function createMessage($body = '', array $properties = [], array $headers = [])
    {
        return new RdKafkaMessage($body, $properties, $headers);
    }

    /**
     * {@inheritdoc}
     *
     * @return RdKafkaTopic
     */
    public function createTopic($topicName)
    {
        return new RdKafkaTopic($topicName);
    }

    /**
     * {@inheritdoc}
     *
     * @return RdKafkaTopic
     */
    public function createQueue($queueName)
    {
        return new RdKafkaTopic($queueName);
    }

    /**
     * {@inheritdoc}
     */
    public function createTemporaryQueue()
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     *
     * @return RdKafkaProducer
     */
    public function createProducer()
    {
        return new RdKafkaProducer($this->getProducer(), $this->getSerializer());
    }

    /**
     * {@inheritdoc}
     *
     * @param RdKafkaTopic $destination
     */
    public function createConsumer(PsrDestination $destination)
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

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $kafkaConsumers = $this->kafkaConsumers;
        $this->kafkaConsumers = [];

        foreach ($kafkaConsumers as $kafkaConsumer) {
            $kafkaConsumer->unsubscribe();
        }
    }

    /**
     * @return Producer
     */
    private function getProducer()
    {
        if (null === $this->producer) {
            $this->producer = new Producer($this->getConf());

            if (isset($this->config['log_level'])) {
                $this->producer->setLogLevel($this->config['log_level']);
            }
        }

        return $this->producer;
    }

    /**
     * @return Conf
     */
    private function getConf()
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
