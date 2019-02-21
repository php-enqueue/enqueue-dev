<?php

declare(strict_types=1);

namespace Enqueue\Redis;

class RedisBlockingConsumeStrategy implements RedisConsumeStrategy
{
    use RedisConsumerHelperTrait;

    /**
     * @var string[]
     */
    private $queueNames;

    /**
     * @var RedisContext
     */
    private $context;

    public function __construct(RedisContext $context)
    {
        $this->context = $context;
    }

    /**
     * @param RedisDestination[] $queues
     * @param int                $timeout
     * @param int                $redeliveryDelay
     *
     * @return RedisMessage|null
     */
    public function receiveMessage(array $queues, int $timeout, int $redeliveryDelay): ?RedisMessage
    {
        $startAt = time();
        $thisTimeout = (int) ceil($timeout / 1000);

        if (null === $this->queueNames) {
            $this->queueNames = [];
            foreach ($queues as $queue) {
                $this->queueNames[] = $queue->getName();
            }
        }

        while ($thisTimeout > 0) {
            $this->migrateExpiredMessages($this->context->getRedis(), $this->queueNames);

            if (false == $result = $this->context->getRedis()->brpop($this->queueNames, $thisTimeout)) {
                return null;
            }

            $this->pushQueueNameBack($this->queueNames, $result->getKey());

            if ($message = $this->processResult($result, $redeliveryDelay)) {
                return $message;
            }

            $thisTimeout -= time() - $startAt;
        }

        return null;
    }

    public function receiveMessageNoWait(RedisDestination $queue, int $redeliveryDelay): ?RedisMessage
    {
        $this->migrateExpiredMessages($this->context->getRedis(), [$queue->getName()]);

        if ($result = $this->context->getRedis()->rpop($queue->getName())) {
            return $this->processResult($result, $redeliveryDelay);
        }

        return null;
    }

    public function resetState()
    {
        $this->queueNames = null;
    }

    protected function processResult(RedisResult $result, int $redeliveryDelay): ?RedisMessage
    {
        $message = $this->context->getSerializer()->toMessage($result->getMessage());

        $now = time();

        if (0 === $message->getAttempts() && $expiresAt = $message->getHeader('expires_at')) {
            if ($now > $expiresAt) {
                return null;
            }
        }

        $message->setHeader('attempts', $message->getAttempts() + 1);
        $message->setRedelivered($message->getAttempts() > 1);
        $message->setKey($result->getKey());
        $message->setReservedKey($this->context->getSerializer()->toString($message));

        $reservedQueue = $result->getKey().':reserved';
        $redeliveryAt = $now + $redeliveryDelay;

        $this->context->getRedis()->zadd($reservedQueue, $message->getReservedKey(), $redeliveryAt);

        return $message;
    }
}
