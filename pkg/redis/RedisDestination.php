<?php

declare(strict_types=1);

namespace Enqueue\Redis;

use Interop\Queue\Queue;
use Interop\Queue\Topic;

class RedisDestination implements Queue, Topic
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getQueueName(): string
    {
        return $this->getName();
    }

    public function getTopicName(): string
    {
        return $this->getName();
    }
}
