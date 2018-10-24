<?php

declare(strict_types=1);

namespace Enqueue\Wamp;

use Interop\Queue\Queue;
use Interop\Queue\Topic;

class WampDestination implements Topic, Queue
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getQueueName(): string
    {
        return $this->name;
    }

    public function getTopicName(): string
    {
        return $this->name;
    }
}
