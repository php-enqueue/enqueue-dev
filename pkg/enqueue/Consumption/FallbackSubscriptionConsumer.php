<?php

namespace Enqueue\Consumption;

use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrSubscriptionConsumer;

final class FallbackSubscriptionConsumer implements PsrSubscriptionConsumer
{
    /**
     * an item contains an array: [PsrConsumer $consumer, callable $callback];.
     * an item key is a queue name.
     *
     * @var array
     */
    private $subscribers;

    /**
     * @var int|float the time in milliseconds the consumer waits if no message has been received
     */
    private $idleTime = 0;

    public function __construct()
    {
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

        while (true) {
            /**
             * @var string
             * @var PsrConsumer $consumer
             * @var callable    $processor
             */
            foreach ($this->subscribers as $queueName => list($consumer, $callback)) {
                $message = $consumer->receiveNoWait();

                if ($message) {
                    if (false === call_user_func($callback, $message, $consumer)) {
                        return;
                    }
                } else {
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

    /**
     * {@inheritdoc}
     */
    public function subscribe(PsrConsumer $consumer, callable $callback)
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

    /**
     * {@inheritdoc}
     */
    public function unsubscribe(PsrConsumer $consumer)
    {
        if (false == array_key_exists($consumer->getQueue()->getQueueName(), $this->subscribers)) {
            return;
        }

        if ($this->subscribers[$consumer->getQueue()->getQueueName()][0] !== $consumer) {
            return;
        }

        unset($this->subscribers[$consumer->getQueue()->getQueueName()]);
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribeAll()
    {
        $this->subscribers = [];
    }

    /**
     * @return float|int
     */
    public function getIdleTime()
    {
        return $this->idleTime;
    }

    /**
     * @param float|int $idleTime
     */
    public function setIdleTime($idleTime)
    {
        $this->idleTime = $idleTime;
    }
}
