<?php

declare(strict_types=1);

namespace Enqueue\Redis;

use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;

class RedisContext implements Context
{
    use SerializerAwareTrait;

    /**
     * @var Redis
     */
    private $redis;

    /**
     * @var callable
     */
    private $redisFactory;

    /**
     * @var int
     */
    private $redeliveryDelay = 300;

    /**
     * Callable must return instance of Redis once called.
     *
     * @param Redis|callable $redis
     * @param int            $redeliveryDelay
     */
    public function __construct($redis, int $redeliveryDelay)
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

        $this->redeliveryDelay = $redeliveryDelay;
        $this->setSerializer(new JsonSerializer());
    }

    /**
     * @return RedisMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        return new RedisMessage($body, $properties, $headers);
    }

    /**
     * @return RedisDestination
     */
    public function createTopic(string $topicName): Topic
    {
        return new RedisDestination($topicName);
    }

    /**
     * @return RedisDestination
     */
    public function createQueue(string $queueName): Queue
    {
        return new RedisDestination($queueName);
    }

    /**
     * @param RedisDestination $queue
     */
    public function deleteQueue(Queue $queue): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, RedisDestination::class);

        $this->deleteDestination($queue);
    }

    /**
     * @param RedisDestination $topic
     */
    public function deleteTopic(Topic $topic): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($topic, RedisDestination::class);

        $this->deleteDestination($topic);
    }

    public function createTemporaryQueue(): Queue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return RedisProducer
     */
    public function createProducer(): Producer
    {
        return new RedisProducer($this);
    }

    /**
     * @param RedisDestination $destination
     *
     * @return RedisConsumer
     */
    public function createConsumer(Destination $destination): Consumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, RedisDestination::class);

        $consumer = new RedisConsumer($this, $destination);
        $consumer->setRedeliveryDelay($this->redeliveryDelay);

        return $consumer;
    }

    /**
     * @return RedisSubscriptionConsumer
     */
    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        $consumer = new RedisSubscriptionConsumer($this);
        $consumer->setRedeliveryDelay($this->redeliveryDelay);

        return $consumer;
    }

    /**
     * @param RedisDestination $queue
     */
    public function purgeQueue(Queue $queue): void
    {
        $this->deleteDestination($queue);
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

    private function deleteDestination(RedisDestination $destination): void
    {
        $this->getRedis()->del($destination->getName());
        $this->getRedis()->del($destination->getName().':delayed');
        $this->getRedis()->del($destination->getName().':reserved');
    }
}
