<?php

namespace Enqueue\Mongodb;

use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;

class MongodbDestination implements PsrTopic, PsrQueue
{
    /**
     * @var string
     */
    private $destinationName;

    public function __construct(string $name)
    {
        $this->destinationName = $name;
    }

    public function getQueueName(): string
    {
        return $this->destinationName;
    }

    public function getTopicName(): string
    {
        return $this->destinationName;
    }

    public function getName(): string
    {
        return $this->destinationName;
    }
}
