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
     * @var RedisConsumeStrategy
     */
    private $consumeStrategy;

    /**
     * @var int
     */
    private $redeliveryDelay = 300;

    public function __construct(
        RedisContext $context,
        RedisDestination $queue,
        RedisConsumeStrategy $consumeStrategy
    ) {
        $this->context = $context;
        $this->queue = $queue;
        $this->consumeStrategy = $consumeStrategy;
    }

    /**
     * @return int
     */
    public function getRedeliveryDelay(): ?int
    {
        return $this->redeliveryDelay;
    }

    /**
     * @param int $delay
     */
    public function setRedeliveryDelay(int $delay): void
    {
        $this->redeliveryDelay = $delay;
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
        if ($timeout <= 0) {
            while (true) {
                if ($message = $this->receive(5000)) {
                    return $message;
                }
            }
        }

        return $this->consumeStrategy->receiveMessage([$this->queue], $timeout, $this->redeliveryDelay);
    }

    /**
     * @return RedisMessage
     */
    public function receiveNoWait(): ?Message
    {
        return $this->consumeStrategy->receiveMessageNoWait($this->queue, $this->redeliveryDelay);
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
            $message = $this->getContext()->getSerializer()->toMessage($message->getReservedKey());
            $message->setHeader('attempts', 0);

            if ($message->getTimeToLive()) {
                $message->setHeader('expires_at', time() + $message->getTimeToLive());
            }

            $payload = $this->getContext()->getSerializer()->toString($message);

            $this->getRedis()->lpush($this->queue->getName(), $payload);
        }
    }

    private function getContext(): RedisContext
    {
        return $this->context;
    }

    private function getRedis(): Redis
    {
        return $this->context->getRedis();
    }
}
