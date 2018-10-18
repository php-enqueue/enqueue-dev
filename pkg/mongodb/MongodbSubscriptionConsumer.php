<?php

declare(strict_types=1);

namespace Enqueue\Mongodb;

use Interop\Queue\Consumer;
use Interop\Queue\SubscriptionConsumer;

class MongodbSubscriptionConsumer implements SubscriptionConsumer
{
    /**
     * @var MongodbContext
     */
    private $context;

    /**
     * an item contains an array: [MongodbConsumer $consumer, callable $callback];.
     *
     * @var array
     */
    private $subscribers;

    /**
     * @param MongodbContext $context
     */
    public function __construct(MongodbContext $context)
    {
        $this->context = $context;
        $this->subscribers = [];
    }

    public function consume(int $timeout = 0): void
    {
        if (empty($this->subscribers)) {
            throw new \LogicException('No subscribers');
        }

        $timeout = (int) ceil($timeout / 1000);
        $endAt = time() + $timeout;

        $queueNames = [];
        foreach (array_keys($this->subscribers) as $queueName) {
            $queueNames[$queueName] = $queueName;
        }

        $currentQueueNames = [];
        while (true) {
            if (empty($currentQueueNames)) {
                $currentQueueNames = $queueNames;
            }

            $result = $this->context->getCollection()->findOneAndDelete(
                [
                    'queue' => ['$in' => array_keys($currentQueueNames)],
                    '$or' => [
                        ['delayed_until' => ['$exists' => false]],
                        ['delayed_until' => ['$lte' => time()]],
                    ],
                ],
                [
                    'sort' => ['priority' => -1, 'published_at' => 1],
                    'typeMap' => ['root' => 'array', 'document' => 'array'],
                ]
            );

            if ($result) {
                list($consumer, $callback) = $this->subscribers[$result['queue']];

                $message = $this->context->convertMessage($result);

                if (false === call_user_func($callback, $message, $consumer)) {
                    return;
                }

                unset($currentQueueNames[$result['queue']]);
            } else {
                $currentQueueNames = [];
            }

            if ($timeout && microtime(true) >= $endAt) {
                return;
            }
        }
    }

    /**
     * @param MongodbConsumer $consumer
     */
    public function subscribe(Consumer $consumer, callable $callback): void
    {
        if (false == $consumer instanceof MongodbConsumer) {
            throw new \InvalidArgumentException(sprintf('The consumer must be instance of "%s" got "%s"', MongodbConsumer::class, get_class($consumer)));
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
     * @param MongodbConsumer $consumer
     */
    public function unsubscribe(Consumer $consumer): void
    {
        if (false == $consumer instanceof MongodbConsumer) {
            throw new \InvalidArgumentException(sprintf('The consumer must be instance of "%s" got "%s"', MongodbConsumer::class, get_class($consumer)));
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

    public function unsubscribeAll(): void
    {
        $this->subscribers = [];
    }
}
