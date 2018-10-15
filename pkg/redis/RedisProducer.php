<?php

namespace Enqueue\Redis;

use Enqueue\RedisTools\DelayStrategyAware;
use Enqueue\RedisTools\DelayStrategyAwareTrait;
use Interop\Queue\DeliveryDelayNotSupportedException;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;

class RedisProducer implements PsrProducer, DelayStrategyAware
{
    use DelayStrategyAwareTrait;
    /**
     * @var Redis
     */
    private $redis;

    /**
     * @var RedisContext
     */
    private $context;

    /**
     * @var int
     */
    private $deliveryDelay;

    /**
     * @param RedisContext $redisContext
     */
    public function __construct(RedisContext $redisContext)
    {
        $this->redis = $redisContext->getRedis();
        $this->context = $redisContext;
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

        if ($this->deliveryDelay) {
            $this->delayStrategy->delayMessage($this->context, $destination, $message, $this->deliveryDelay);
        } else {
            $this->redis->lpush($destination->getName(), json_encode($message));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDeliveryDelay($deliveryDelay)
    {
        if (null === $deliveryDelay) {
            return;
        }

        if (null === $this->delayStrategy) {
            throw DeliveryDelayNotSupportedException::providerDoestNotSupportIt();
        }

        $this->deliveryDelay = $deliveryDelay;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryDelay()
    {
        return $this->deliveryDelay;
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
