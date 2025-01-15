<?php

declare(strict_types=1);

namespace Enqueue\Redis;

interface Redis
{
    /**
     * @throws ServerException
     */
    public function eval(string $script, array $keys = [], array $args = []);

    /**
     * @throws ServerException
     */
    public function zadd(string $key, string $value, float $score): int;

    /**
     * @throws ServerException
     */
    public function zrem(string $key, string $value): int;

    /**
     * @throws ServerException
     *
     * @return int length of the list
     */
    public function lpush(string $key, string $value): int;

    /**
     * @param string[] $keys
     * @param int      $timeout in seconds
     *
     * @throws ServerException
     */
    public function brpop(array $keys, int $timeout): ?RedisResult;

    /**
     * @throws ServerException
     */
    public function rpop(string $key): ?RedisResult;

    /**
     * @throws ServerException
     */
    public function connect(): void;

    public function disconnect(): void;

    /**
     * @throws ServerException
     */
    public function del(string $key): void;
}
