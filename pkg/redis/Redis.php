<?php

declare(strict_types=1);

namespace Enqueue\Redis;

interface Redis
{
    /**
     * @param string $key
     * @param string $value
     *
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
     *
     * @return RedisResult|null
     */
    public function brpop(array $keys, int $timeout): ?RedisResult;

    /**
     * @param string $key
     *
     * @throws ServerException
     *
     * @return RedisResult|null
     */
    public function rpop(string $key): ?RedisResult;

    /**
     * @throws ServerException
     */
    public function connect(): void;

    public function disconnect(): void;

    /**
     * @param string $key
     *
     * @throws ServerException
     */
    public function del(string $key): void;
}
