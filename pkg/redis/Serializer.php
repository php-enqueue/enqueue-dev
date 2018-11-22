<?php

declare(strict_types=1);

namespace Enqueue\Redis;

interface Serializer
{
    public function toString(RedisMessage $message): string;

    public function toMessage(string $string): RedisMessage;
}
