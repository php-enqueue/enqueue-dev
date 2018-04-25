<?php

namespace Enqueue\Gearman;

use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;

class GearmanDestination implements PsrQueue, PsrTopic
{
    /**
     * @var string
     */
    private $destinationName;

    /**
     * @param string $destinationName
     */
    public function __construct($destinationName)
    {
        $this->destinationName = $destinationName;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->destinationName;
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
