<?php

declare(strict_types=1);

namespace Enqueue\Redis;

interface RedisConsumeStrategy
{
    public function receiveMessage(array $queues, int $timeout, int $redeliveryDelay): ?RedisMessage;

    public function receiveMessageNoWait(RedisDestination $queue, int $redeliveryDelay): ?RedisMessage;

    public function resetState();
}
