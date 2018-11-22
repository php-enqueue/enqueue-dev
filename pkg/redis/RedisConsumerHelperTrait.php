<?php

declare(strict_types=1);

namespace Enqueue\Redis;

trait RedisConsumerHelperTrait
{
    /**
     * @var string[]
     */
    protected $queueNames;

    abstract protected function getContext(): RedisContext;

    /**
     * @param RedisDestination[] $queues
     * @param int                $timeout
     * @param int                $redeliveryDelay
     *
     * @return RedisMessage|null
     */
    protected function receiveMessage(array $queues, int $timeout, int $redeliveryDelay): ?RedisMessage
    {
        $startAt = time();
        $thisTimeout = $timeout;

        if (null === $this->queueNames) {
            $this->queueNames = [];
            foreach ($queues as $queue) {
                $this->queueNames[] = $queue->getName();
            }
        }

        while ($thisTimeout > 0) {
            $this->migrateExpiredMessages($this->queueNames);

            if (false == $result = $this->getContext()->getRedis()->brpop($this->queueNames, $thisTimeout)) {
                return null;
            }

            $this->pushQueueNameBack($result->getKey());

            if ($message = $this->processResult($result, $redeliveryDelay)) {
                return $message;
            }

            $thisTimeout -= time() - $startAt;
        }

        return null;
    }

    protected function receiveMessageNoWait(RedisDestination $destination, int $redeliveryDelay): ?RedisMessage
    {
        $this->migrateExpiredMessages([$destination->getName()]);

        if ($result = $this->getContext()->getRedis()->rpop($destination->getName())) {
            return $this->processResult($result, $redeliveryDelay);
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
        $redeliveryAt = $now + $redeliveryDelay;

        $this->getContext()->getRedis()->zadd($reservedQueue, $message->getReservedKey(), $redeliveryAt);

        return $message;
    }

    protected function pushQueueNameBack(string $queueName): void
    {
        if (count($this->queueNames) <= 1) {
            return;
        }

        if (false === $from = array_search($queueName, $this->queueNames, true)) {
            throw new \LogicException(sprintf('Queue name was not found: "%s"', $queueName));
        }

        $to = count($this->queueNames) - 1;

        $out = array_splice($this->queueNames, $from, 1);
        array_splice($this->queueNames, $to, 0, $out);
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
}
