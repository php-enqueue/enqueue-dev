<?php

declare(strict_types=1);

namespace Enqueue\Redis;

use Interop\Queue\Consumer;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Queue;

class RedisConsumer implements Consumer
{
    use RedisConsumerHelperTrait;

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
    private $redeliveryDelay = 300;

    /**
     * @var int
     */
    private $deliveryDelay;

    public function __construct(RedisContext $context, RedisDestination $queue)
    {
        $this->context = $context;
        $this->queue = $queue;
    }

    /**
     * @return int
     */
    public function getRedeliveryDelay(): ?int
    {
        return $this->redeliveryDelay;
    }

    public function setRedeliveryDelay(int $delay): void
    {
        $this->redeliveryDelay = $delay;
    }

    /**
     * @return int
     */
    public function getDeliveryDelay(): ?int
    {
        return $this->deliveryDelay;
    }

    public function setDeliveryDelay(int $deliveryDelay): void
    {
        $this->deliveryDelay = $deliveryDelay;
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

        return $this->receiveMessage([$this->queue], $timeout, $this->redeliveryDelay);
    }

    /**
     * @return RedisMessage
     */
    public function receiveNoWait(): ?Message
    {
        return $this->receiveMessageNoWait($this->queue, $this->redeliveryDelay);
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
    public function reject(Message $message, bool $requeue = false, $delay = 0): void
    {
        InvalidMessageException::assertMessageInstanceOf($message, RedisMessage::class);

        $this->acknowledge($message);

        if ($requeue) {
            $message = $this->getContext()->getSerializer()->toMessage($message->getReservedKey());
            $message->setRedelivered(true);

            if (null !== $this->deliveryDelay && null === $message->getDeliveryDelay()) {
                $message->setDeliveryDelay($this->deliveryDelay);
            }

            if ($message->getTimeToLive()) {
                $message->setHeader('expires_at', time() + $message->getTimeToLive());
            }

            if($message->getDeliveryDelay()) {
                $deliveryAt = time() + $message->getDeliveryDelay() / 1000;
                $payload = $this->getContext()->getSerializer()->toString($message);
                $this->getRedis()->zadd($this->queue->getName().':delayed', $payload, $deliveryAt);
            } else {
                $payload = $this->getContext()->getSerializer()->toString($message);
                $this->getRedis()->lpush($this->queue->getName(), $payload);
            }
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
