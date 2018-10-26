<?php

declare(strict_types=1);

namespace Enqueue\Redis;

use Enqueue\Util\UUID;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\InvalidMessageException;
use Interop\Queue\Message;
use Interop\Queue\Producer;

class RedisProducer implements Producer
{
    /**
     * @var Redis
     */
    private $redis;

    /**
     * @var int|null
     */
    private $timeToLive;

    /**
     * @var int
     */
    private $deliveryDelay;

    /**
     * @param Redis $redis
     */
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param RedisDestination $destination
     * @param RedisMessage     $message
     */
    public function send(Destination $destination, Message $message): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, RedisDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, RedisMessage::class);

        $message->setMessageId(UUID::generate());
        $message->setHeader('attempts', 0);

        if (null !== $this->timeToLive && null === $message->getTimeToLive()) {
            $message->setTimeToLive($this->timeToLive);
        }

        if (null !== $this->deliveryDelay && null === $message->getDeliveryDelay()) {
            $message->setDeliveryDelay($this->deliveryDelay);
        }

        if ($message->getTimeToLive()) {
            $message->setHeader('expires_at', time() + $message->getTimeToLive());
        }

        if ($message->getDeliveryDelay()) {
            $deliveryAt = time() + $message->getDeliveryDelay();
            $this->redis->zadd($destination->getName().':delayed', json_encode($message), $deliveryAt);
        } else {
            $this->redis->lpush($destination->getName(), json_encode($message));
        }
    }

    /**
     * @return self
     */
    public function setDeliveryDelay(int $deliveryDelay = null): Producer
    {
        $this->deliveryDelay = $deliveryDelay;

        return $this;
    }

    public function getDeliveryDelay(): ?int
    {
        return $this->deliveryDelay;
    }

    /**
     * @return RedisProducer
     */
    public function setPriority(int $priority = null): Producer
    {
        if (null === $priority) {
            return $this;
        }

        throw new \LogicException('Not implemented');
    }

    public function getPriority(): ?int
    {
        return null;
    }

    /**
     * @return self
     */
    public function setTimeToLive(int $timeToLive = null): Producer
    {
        $this->timeToLive = $timeToLive;

        return $this;
    }

    public function getTimeToLive(): ?int
    {
        return $this->timeToLive;
    }
}
