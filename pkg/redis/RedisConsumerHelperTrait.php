<?php

declare(strict_types=1);

namespace Enqueue\Redis;

trait RedisConsumerHelperTrait
{
    abstract protected function getContext(): RedisContext;

    /**
     * @param RedisDestination   $destination
     * @param int                $timeout
     * @param int                $redeliveryDelay
     *
     * @return RedisMessage|null
     */
    protected function receiveMessage(RedisDestination $destination, int $timeout, int $redeliveryDelay): ?RedisMessage
    {
        $startAt = time();
        $thisTimeout = $timeout;

        while ($thisTimeout > 0) {
            $queueName = $destination->getName();
            $this->migrateExpiredMessages([$queueName]);

            if (false == $result = $this->getContext()->getRedis()->brpoplpush(
                $queueName, $queueName.':processing', $thisTimeout
            )) {
                $this->migrateProcessingMessages([$queueName]);
                return null;
            }

            if ($message = $this->processResult($result, $redeliveryDelay)) {
                return $message;
            }

            $thisTimeout -= time() - $startAt;
        }

        return null;
    }

    protected function receiveMessageNoWait(RedisDestination $destination, int $redeliveryDelay): ?RedisMessage
    {
        $queueName = $destination->getName();
        $this->migrateExpiredMessages([$queueName]);

        if ($result = $this->getContext()->getRedis()->rpoplpush(
            $queueName, $queueName.':processing'
        )) {
            return $this->processResult($result, $redeliveryDelay);
        } else {
            $this->migrateProcessingMessages([$queueName]);
        }

        return null;
    }

    protected function processResult(RedisResult $result, int $redeliveryDelay): ?RedisMessage
    {
        $message = $this->getContext()->getSerializer()->toMessage($result->getMessage());

        $now = time();

        if (0 === $message->getAttempts() && $expiresAt = $message->getHeader('expires_at')) {
            if ($now > $expiresAt) {
                return null;
            }
        }

        $message->setHeader('attempts', $message->getAttempts() + 1);
        $message->setRedelivered($message->getAttempts() > 1);
        $message->setKey($result->getKey());
        $message->setReservedKey($this->getContext()->getSerializer()->toString($message));

        $reservedQueue = $result->getKey().':reserved';
        $processingQueue = $result->getKey().':processing';
        $redeliveryAt = $now + $redeliveryDelay;

        $redis = $this->getContext()->getRedis();
        $redis->zadd($reservedQueue, $message->getReservedKey(), $redeliveryAt);
        $redis->lrem($processingQueue, 0, $result->getMessage());
        return $message;
    }

    protected function migrateExpiredMessages(array $queueNames): void
    {
        $now = time();

        foreach ($queueNames as $queueName) {
            $this->getContext()->getRedis()
                ->eval(LuaScripts::migrateExpired(), [$queueName.':delayed', $queueName], [$now]);

            $this->getContext()->getRedis()
                ->eval(LuaScripts::migrateExpired(), [$queueName.':reserved', $queueName], [$now]);
        }
    }

    protected function migrateProcessingMessages(array $queueNames): int
    {
        foreach ($queueNames as $queueName) {
            $this->getContext()->getRedis()
                ->renamenx($queueName.':processing', $queueName);
        }

    }
}
