<?php

namespace Enqueue\Redis;

use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\InvalidMessageException;
use Enqueue\Psr\PsrDestination;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProducer;

class RedisProducer implements PsrProducer
{
    /**
     * @var Redis
     */
    private $redis;

    /**
     * @param Redis $redis
     */
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * {@inheritdoc}
     *
     * @param RedisDestination $destination
     * @param RedisMessage     $message
     */
    public function send(PsrDestination $destination, PsrMessage $message)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, RedisDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, RedisMessage::class);

        $this->redis->lpush($destination->getName(), json_encode($message));
    }
}
