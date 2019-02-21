<?php

declare(strict_types=1);

namespace Enqueue\Redis;

trait RedisConsumerHelperTrait
{
    protected function pushQueueNameBack(array &$queueNames, string $queueName): void
    {
        if (count($queueNames) <= 1) {
            return;
        }

        if (false === $from = array_search($queueName, $queueNames, true)) {
            throw new \LogicException(sprintf('Queue name was not found: "%s"', $queueName));
        }

        $to = count($queueNames) - 1;

        $out = array_splice($queueNames, $from, 1);
        array_splice($queueNames, $to, 0, $out);
    }

    protected function migrateExpiredMessages(Redis $redis, array $queueNames): void
    {
        $now = time();

        foreach ($queueNames as $queueName) {
            $redis->eval(LuaScripts::migrateExpired(), [$queueName.':delayed', $queueName], [$now]);
            $redis->eval(LuaScripts::migrateExpired(), [$queueName.':reserved', $queueName], [$now]);
        }
    }
}
