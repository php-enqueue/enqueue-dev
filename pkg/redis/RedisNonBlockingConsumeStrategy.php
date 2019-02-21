<?php

declare(strict_types=1);

namespace Enqueue\Redis;

class RedisNonBlockingConsumeStrategy implements RedisConsumeStrategy
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
        $endAt = microtime(true) + $timeout / 1000;

        if (null === $this->queueNames) {
            $this->queueNames = [];
            foreach ($queues as $queue) {
                $this->queueNames[] = $queue->getName();
            }
        }

        while (true) {
            $this->migrateExpiredMessages($this->context->getRedis(), $this->queueNames);

            $queue = current($this->queueNames);
            $reservedQueue = $queue.':reserved';
            $now = time();
            $redeliveryAt = $now + $redeliveryDelay;

            $this->pushQueueNameBack($this->queueNames, $queue);

            if ($result = $this->context->getRedis()->eval(LuaScripts::receiveMessage(), [$queue, $reservedQueue], [$now, $redeliveryAt])) {
                return $this->processResult($result, $queue);
            }

            if (microtime(true) > $endAt) {
                return null;
            }

            usleep(10000);
        }
    }

    public function receiveMessageNoWait(RedisDestination $queue, int $redeliveryDelay): ?RedisMessage
    {
        $this->migrateExpiredMessages($this->context->getRedis(), [$queue->getName()]);

        if ($result = $this->context->getRedis()->rpop($queue->getName())) {
            return $this->processResult($result->getMessage(), $queue->getName());
        }

        return null;
    }

    public function resetState()
    {
        $this->queueNames = null;
    }

    protected function processResult(string $result, string $key): ?RedisMessage
    {
        $message = $this->context->getSerializer()->toMessage($result);
        $message->setKey($key);
        $message->setReservedKey($result);
        $message->setRedelivered($message->getAttempts() > 1);

        return $message;
    }
}
