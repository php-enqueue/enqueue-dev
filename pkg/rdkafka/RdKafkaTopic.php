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
     * @param string         $name
     * @param TopicConf|null $conf
     */
    public function __construct($name, TopicConf $conf = null)
    {
        $this->name = $name;

        if (!$conf) {
            $conf = new TopicConf();
        }

        $this->conf = $conf;
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
     * @return TopicConf
     */
    public function getConf()
    {
        return $this->conf;
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
