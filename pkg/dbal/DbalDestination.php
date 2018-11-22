<?php

declare(strict_types=1);

namespace Enqueue\Dbal;

use Interop\Queue\Queue;
use Interop\Queue\Topic;

class DbalDestination implements Topic, Queue
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
}
