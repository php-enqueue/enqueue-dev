<?php

namespace Enqueue\Redis;

use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;

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
     * {@inheritdoc}
     *
     * @return RedisMessage
     */
    public function createMessage($body = '', array $properties = [], array $headers = [])
    {
        return new RedisMessage($body, $properties, $headers);
    }

    /**
     * {@inheritdoc}
     *
     * @return RedisDestination
     */
    public function createTopic($topicName)
    {
        return new RedisDestination($topicName);
    }

    /**
     * {@inheritdoc}
     *
     * @return RedisDestination
     */
    public function createQueue($queueName)
    {
        return new RedisDestination($queueName);
    }

    /**
     * @param RedisDestination|PsrQueue $queue
     */
    public function deleteQueue(PsrQueue $queue)
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, RedisDestination::class);

        $this->getRedis()->del($queue->getName());
    }

    /**
     * @param RedisDestination|PsrTopic $topic
     */
    public function deleteTopic(PsrTopic $topic)
    {
        InvalidDestinationException::assertDestinationInstanceOf($topic, RedisDestination::class);

        $this->getRedis()->del($topic->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function createTemporaryQueue()
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     *
     * @return RedisProducer
     */
    public function createProducer()
    {
        return new RedisProducer($this->getRedis());
    }

    /**
     * {@inheritdoc}
     *
     * @param RedisDestination $destination
     *
     * @return RedisConsumer
     */
    public function createConsumer(PsrDestination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, RedisDestination::class);

        return new RedisConsumer($this, $destination);
    }

    public function close()
    {
        $this->getRedis()->disconnect();
    }

    /**
     * @return Redis
     */
    public function getRedis()
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
