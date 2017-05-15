<?php

namespace Enqueue\Dbal;

use Enqueue\Psr\PsrQueue;
use Enqueue\Psr\PsrTopic;

class DbalDestination implements PsrTopic, PsrQueue
{
    /**
     * @var string
     */
    private $destinationName;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->destinationName = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function getQueueName()
    {
        return $this->destinationName;
    }

    /**
     * {@inheritdoc}
     */
    public function getTopicName()
    {
        return $this->destinationName;
    }
}
