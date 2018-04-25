<?php

namespace Enqueue\Redis;

use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;

class RedisProducer implements PsrProducer
{
    /**
     * @var Redis
     */
    private $redis;

    /**
     * @param Redis $redis
     */
    public function __construct(Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * {@inheritdoc}
     *
     * @param RedisDestination $destination
     * @param RedisMessage     $message
     */
    public function send(PsrDestination $destination, PsrMessage $message)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, RedisDestination::class);
        InvalidMessageException::assertMessageInstanceOf($message, RedisMessage::class);

        $this->redis->lpush($destination->getName(), json_encode($message));
    }

    /**
     * {@inheritdoc}
     */
    public function setDeliveryDelay($deliveryDelay)
    {
        if (null === $deliveryDelay) {
            return;
        }

        throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryDelay()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority($priority)
    {
        if (null === $priority) {
            return;
        }

        throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setTimeToLive($timeToLive)
    {
        if (null === $timeToLive) {
            return;
        }

        throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeToLive()
    {
        return null;
    }
}
