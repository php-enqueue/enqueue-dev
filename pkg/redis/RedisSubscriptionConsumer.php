<?php

namespace Enqueue\Redis;

use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrSubscriptionConsumer;

class RedisSubscriptionConsumer implements PsrSubscriptionConsumer
{
    /**
     * @var RedisContext
     */
    private $context;

    /**
     * an item contains an array: [RedisConsumer $consumer, callable $callback];.
     *
     * @var array
     */
    private $subscribers;

    /**
     * @param RedisContext $context
     */
    public function __construct(RedisContext $context)
    {
        $this->context = $context;
        $this->subscribers = [];
    }

    /**
     * {@inheritdoc}
     */
    public function consume($timeout = 0)
    {
        if (empty($this->subscribers)) {
            throw new \LogicException('No subscribers');
        }

        $timeout /= 1000;
        $endAt = microtime(true) + $timeout;

        $queueNames = [];
        foreach (array_keys($this->subscribers) as $queueName) {
            $queueNames[$queueName] = $queueName;
        }

        $currentQueueNames = [];
        while (true) {
            if (empty($currentQueueNames)) {
                $currentQueueNames = $queueNames;
            }

            /**
             * @var string
             * @var PsrConsumer $consumer
             * @var callable    $processor
             */
            $result = $this->context->getRedis()->brpop($currentQueueNames, $timeout || 5000);
            if ($result) {
                $message = RedisMessage::jsonUnserialize($result->getMessage());
                list($consumer, $callback) = $this->subscribers[$result->getKey()];
                if (false === call_user_func($callback, $message, $consumer)) {
                    return;
                }

                unset($currentQueueNames[$result->getKey()]);
            } else {
                $currentQueueNames = [];

                if ($timeout && microtime(true) >= $endAt) {
                    return;
                }
            }

            if ($timeout && microtime(true) >= $endAt) {
                return;
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @param RedisConsumer $consumer
     */
    public function subscribe(PsrConsumer $consumer, callable $callback)
    {
        if (false == $consumer instanceof RedisConsumer) {
            throw new \InvalidArgumentException(sprintf('The consumer must be instance of "%s" got "%s"', RedisConsumer::class, get_class($consumer)));
        }

        $queueName = $consumer->getQueue()->getQueueName();
        if (array_key_exists($queueName, $this->subscribers)) {
            if ($this->subscribers[$queueName][0] === $consumer && $this->subscribers[$queueName][1] === $callback) {
                return;
            }

            throw new \InvalidArgumentException(sprintf('There is a consumer subscribed to queue: "%s"', $queueName));
        }

        $this->subscribers[$queueName] = [$consumer, $callback];
    }

    /**
     * {@inheritdoc}
     *
     * @param RedisConsumer $consumer
     */
    public function unsubscribe(PsrConsumer $consumer)
    {
        if (false == $consumer instanceof RedisConsumer) {
            throw new \InvalidArgumentException(sprintf('The consumer must be instance of "%s" got "%s"', RedisConsumer::class, get_class($consumer)));
        }

        $queueName = $consumer->getQueue()->getQueueName();

        if (false == array_key_exists($queueName, $this->subscribers)) {
            return;
        }

        if ($this->subscribers[$queueName][0] !== $consumer) {
            return;
        }

        unset($this->subscribers[$queueName]);
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribeAll()
    {
        $this->subscribers = [];
    }
}
