<?php

namespace Enqueue\RdKafka;

use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;
use RdKafka\TopicConf;

class RdKafkaTopic implements PsrTopic, PsrQueue
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var TopicConf
     */
    private $conf;

    /**
     * @var int
     */
    private $partition;

    /**
     * @var string|null
     */
    private $key;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getTopicName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueName()
    {
        return $this->name;
    }

    /**
     * @return TopicConf|null
     */
    public function getConf()
    {
        return $this->conf;
    }

    /**
     * @param TopicConf|null $conf
     */
    public function setConf(TopicConf $conf = null)
    {
        $this->conf = $conf;
    }

    /**
     * @return int
     */
    public function getPartition()
    {
        return $this->partition;
    }

    /**
     * @param int $partition
     */
    public function setPartition($partition)
    {
        $this->partition = $partition;
    }

    /**
     * @return string|null
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @param string|null $key
     */
    public function setKey($key)
    {
        $this->key = $key;
    }
}
