<?php

namespace Enqueue\Consumption;

use Interop\Queue\Consumer;
use Interop\Queue\SubscriptionConsumer;

final class FallbackSubscriptionConsumer implements SubscriptionConsumer
{
    /**
     * an item contains an array: [Consumer $consumer, callable $callback];.
     * an item key is a queue name.
     *
     * @var array
     */
    private $subscribers;

    /**
     * @var int
     */
    private $idleTime = 0;

    public function __construct()
    {
        $this->subscribers = [];
    }

    public function consume(int $timeoutMs = 0): void
    {
        if (!$subscriberCount = \count($this->subscribers)) {
            throw new \LogicException('No subscribers');
        }

        $timeout = $timeoutMs / 1000;
        $endAt = microtime(true) + $timeout;

        while (true) {
            /**
             * @var string
             * @var Consumer $consumer
             * @var callable $processor
             */
            foreach ($this->subscribers as $queueName => list($consumer, $callback)) {
                $message = 1 === $subscriberCount ? $consumer->receive($timeoutMs) : $consumer->receiveNoWait();

                if ($message) {
                    if (false === call_user_func($callback, $message, $consumer)) {
                        return;
                    }
                } elseif (1 !== $subscriberCount) {
                    if ($timeout && microtime(true) >= $endAt) {
                        return;
                    }

                    $this->idleTime && usleep($this->idleTime);
                }

                if ($timeout && microtime(true) >= $endAt) {
                    return;
                }
            }
        }
    }

    public function subscribe(Consumer $consumer, callable $callback): void
    {
        $queueName = $consumer->getQueue()->getQueueName();
        if (array_key_exists($queueName, $this->subscribers)) {
            if ($this->subscribers[$queueName][0] === $consumer && $this->subscribers[$queueName][1] === $callback) {
                return;
            }

            throw new \InvalidArgumentException(sprintf('There is a consumer subscribed to queue: "%s"', $queueName));
        }

        $this->subscribers[$queueName] = [$consumer, $callback];
    }

    public function unsubscribe(Consumer $consumer): void
    {
        if (false == array_key_exists($consumer->getQueue()->getQueueName(), $this->subscribers)) {
            return;
        }

        if ($this->subscribers[$consumer->getQueue()->getQueueName()][0] !== $consumer) {
            return;
        }

        unset($this->subscribers[$consumer->getQueue()->getQueueName()]);
    }

    public function unsubscribeAll(): void
    {
        $this->subscribers = [];
    }

    public function getIdleTime(): int
    {
        return $this->idleTime;
    }

    /**
     * The time in milliseconds the consumer waits if no message has been received.
     */
    public function setIdleTime(int $idleTime): void
    {
        $this->idleTime = $idleTime;
    }
}
