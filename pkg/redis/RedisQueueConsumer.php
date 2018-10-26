<?php

declare(strict_types=1);

namespace Enqueue\Redis;

class RedisQueueConsumer
{
    /**
     * @var Redis
     */
    private $redis;

    /**
     * @var RedisDestination[]
     */
    private $queues;

    /**
     * Return back message into the queue if message was not acknowledged or rejected after this delay.
     * It could happen if consumer has failed with fatal error or even if message processing is slow
     * and takes more than this time.
     *
     * @var int
     */
    private $retryDelay = 300;

    /**
     * @var string[]
     */
    private $queueNames;

    /**
     * @param Redis              $redis
     * @param RedisDestination[] $queues
     */
    public function __construct(Redis $redis, array $queues)
    {
        $this->redis = $redis;
        $this->queues = $queues;
    }

    /**
     * @return int
     */
    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }

    /**
     * @param int $retryDelay
     */
    public function setRetryDelay(int $retryDelay): void
    {
        $this->retryDelay = $retryDelay;
    }

    public function receiveMessage(int $timeout): ?RedisMessage
    {
        $startAt = time();
        $thisTimeout = $timeout;

        if (null === $this->queueNames) {
            foreach ($this->queues as $queue) {
                $this->queueNames[] = $queue->getName();
            }
        }

        while ($thisTimeout > 0) {
            $this->migrateExpiredMessages($this->queueNames);

            if ($result = $this->redis->brpop($this->queueNames, $thisTimeout)) {
                $this->pushQueueNameBack($result->getKey());

                if ($message = $this->processResult($result)) {
                    return $message;
                }

                $thisTimeout -= time() - $startAt;
            }
        }

        return null;
    }

    public function receiveMessageNoWait(RedisDestination $destination): ?RedisMessage
    {
        $this->migrateExpiredMessages([$destination->getName()]);

        if ($result = $this->redis->rpop($destination->getName())) {
            return $this->processResult($result);
        }

        return null;
    }

    private function processResult(RedisResult $result): ?RedisMessage
    {
        $message = RedisMessage::jsonUnserialize($result->getMessage());

        $now = time();

        if ($expiresAt = $message->getHeader('expires_at')) {
            if ($now > $expiresAt) {
                return null;
            }
        }

        $message->setHeader('attempts', $message->getAttempts() + 1);
        $message->setRedelivered($message->getAttempts() > 1);
        $message->setReservedKey(json_encode($message));
        $message->setKey($result->getKey());

        $reservedQueue = $result->getKey().':reserved';
        $retryMessageAt = $now + $this->retryDelay;

        $this->redis->zadd($reservedQueue, $message->getReservedKey(), $retryMessageAt);

        return $message;
    }

    private function pushQueueNameBack($queueName): void
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

    private function migrateExpiredMessages(array $queueNames): void
    {
        $now = time();

        foreach ($queueNames as $queueName) {
            $this->redis->eval(LuaScripts::migrateExpired(), [$queueName.':delayed', $queueName], [$now]);
            $this->redis->eval(LuaScripts::migrateExpired(), [$queueName.':reserved', $queueName], [$now]);
        }
    }
}
