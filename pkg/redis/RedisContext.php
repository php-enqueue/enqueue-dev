<?php

declare(strict_types=1);

namespace Enqueue\Redis;

use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrSubscriptionConsumer;
use Interop\Queue\PsrTopic;
use Interop\Queue\TemporaryQueueNotSupportedException;

class RedisContext implements PsrContext
{
    /**
     * @var Redis
     */
    private $redis;

    /**
     * @var callable
     */
    private $redisFactory;

    /**
     * Callable must return instance of Redis once called.
     *
     * @param Redis|callable $redis
     */
    public function __construct($redis)
    {
        if ($redis instanceof Redis) {
            $this->redis = $redis;
        } elseif (is_callable($redis)) {
            $this->redisFactory = $redis;
        } else {
            throw new \InvalidArgumentException(sprintf(
                'The $redis argument must be either %s or callable that returns %s once called.',
                Redis::class,
                Redis::class
            ));
        }
    }

    /**
     * @return RedisMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): PsrMessage
    {
        return new RedisMessage($body, $properties, $headers);
    }

    /**
     * @return RedisDestination
     */
    public function createTopic(string $topicName): PsrTopic
    {
        return new RedisDestination($topicName);
    }

    /**
     * @return RedisDestination
     */
    public function createQueue(string $queueName): PsrQueue
    {
        return new RedisDestination($queueName);
    }

    /**
     * @param RedisDestination $queue
     */
    public function deleteQueue(PsrQueue $queue): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, RedisDestination::class);

        $this->getRedis()->del($queue->getName());
    }

    /**
     * @param RedisDestination $topic
     */
    public function deleteTopic(PsrTopic $topic): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($topic, RedisDestination::class);

        $this->getRedis()->del($topic->getName());
    }

    public function createTemporaryQueue(): PsrQueue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return RedisProducer
     */
    public function createProducer(): PsrProducer
    {
        return new RedisProducer($this->getRedis());
    }

    /**
     * @param RedisDestination $destination
     *
     * @return RedisConsumer
     */
    public function createConsumer(PsrDestination $destination): PsrConsumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, RedisDestination::class);

        return new RedisConsumer($this, $destination);
    }

    /**
     * @return RedisSubscriptionConsumer
     */
    public function createSubscriptionConsumer(): PsrSubscriptionConsumer
    {
        return new RedisSubscriptionConsumer($this);
    }

    /**
     * @param RedisDestination $queue
     */
    public function purgeQueue(PsrQueue $queue): void
    {
        $this->getRedis()->del($queue->getName());
    }

    public function close(): void
    {
        $this->getRedis()->disconnect();
    }

    public function getRedis(): Redis
    {
        if (false == $this->redis) {
            $redis = call_user_func($this->redisFactory);
            if (false == $redis instanceof Redis) {
                throw new \LogicException(sprintf(
                    'The factory must return instance of %s. It returned %s',
                    Redis::class,
                    is_object($redis) ? get_class($redis) : gettype($redis)
                ));
            }

            $this->redis = $redis;
        }

        return $this->redis;
    }
}
