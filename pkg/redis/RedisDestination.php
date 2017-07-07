<?php

namespace Enqueue\Redis;

use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;

class RedisDestination implements PsrQueue, PsrTopic
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueName()
    {
        return $this->getName();
    }

    /**
     * {@inheritdoc}
     */
    public function getTopicName()
    {
        return $this->getName();
    }
}
