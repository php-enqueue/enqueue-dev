<?php

declare(strict_types=1);

namespace Enqueue\Redis;

use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Queue;

class RedisConsumer implements Consumer
{
    /**
     * @var RedisDestination
     */
    private $queue;

    /**
     * @var RedisContext
     */
    private $context;

    /**
     * @var int
     */
    private $retryDelay;

    /**
     * @var RedisQueueConsumer
     */
    private $queueConsumer;

    public function __construct(RedisContext $context, RedisDestination $queue)
    {
        $this->context = $context;
        $this->queue = $queue;
    }

    /**
     * @return int
     */
    public function getRetryDelay(): ?int
    {
        return $this->retryDelay;
    }

    /**
     * @param int $retryDelay
     */
    public function setRetryDelay(int $retryDelay): void
    {
        $this->retryDelay = $retryDelay;

        if ($this->queueConsumer) {
            $this->queueConsumer->setRetryDelay($this->retryDelay);
        }
    }

    /**
     * @return RedisDestination
     */
    public function getQueue(): Queue
    {
        return $this->queue;
    }

    /**
     * @return RedisMessage
     */
    public function receive(int $timeout = 0): ?Message
    {
        $timeout = (int) ceil($timeout / 1000);

        if ($timeout <= 0) {
            while (true) {
                if ($message = $this->receive(5000)) {
                    return $message;
                }
            }
        }

        $this->initQueueConsumer();

        return $this->queueConsumer->receiveMessage($timeout);
    }

    /**
     * @return RedisMessage
     */
    public function receiveNoWait(): ?Message
    {
        $this->initQueueConsumer();

        return $this->queueConsumer->receiveMessageNoWait($this->queue);
    }

    /**
     * @param RedisMessage $message
     */
    public function acknowledge(Message $message): void
    {
        $this->getRedis()->zrem($this->queue->getName().':reserved', $message->getReservedKey());
    }

    /**
     * @param RedisMessage $message
     */
    public function reject(Message $message, bool $requeue = false): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, RedisMessage::class);

        $this->acknowledge($message);

        if ($requeue) {
            $message = RedisMessage::jsonUnserialize($message->getReservedKey());
            $message->setHeader('attempts', 0);

            if ($message->getTimeToLive()) {
                $message->setHeader('expires_at', time() + $message->getTimeToLive());
            }

            $this->getRedis()->lpush($this->queue->getName(), json_encode($message));
        }
    }

    private function getRedis(): Redis
    {
        return $this->context->getRedis();
    }

    private function initQueueConsumer(): void
    {
        if (null === $this->queueConsumer) {
            $this->queueConsumer = new RedisQueueConsumer($this->getRedis(), [$this->queue]);

            if ($this->retryDelay) {
                $this->queueConsumer->setRetryDelay($this->retryDelay);
            }
        }
    }
}
