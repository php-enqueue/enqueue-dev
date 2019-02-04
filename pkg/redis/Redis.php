<?php

declare(strict_types=1);

namespace Enqueue\Redis;

interface Redis
{
    /**
     * @param string $script
     * @param array  $keys
     * @param array  $args
     *
     * @throws ServerException
     *
     * @return mixed
     */
    public function eval(string $script, array $keys = [], array $args = []);

    /**
     * @param string $key
     * @param string $value
     * @param float  $score
     *
     * @throws ServerException
     *
     * @return int
     */
    public function zadd(string $key, string $value, float $score): int;

    /**
     * @param string $key
     * @param string $value
     *
     * @throws ServerException
     *
     * @return int
     */
    public function zrem(string $key, string $value): int;

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
     * @param string $key
     * @param int $count
     * @param string $value
     *
     * @throws ServerException
     *
     * @return int number of removed elements
     */
    public function lrem(string $key, int $count, string $value): int;

    /**
     * @param string $key
     * @param string $target
     *
     * @throws ServerException
     *
     * @return int rename key to non-exists target success
     */
    public function renamenx(string $key, string $target): int;

    /**
     * @param string   $source
     * @param string   $dest
     * @param int      $timeout in seconds
     *
     * @throws ServerException
     *
     * @return RedisResult|null
     */
    public function brpoplpush(string $source, string $dest, int $timeout): ?RedisResult;

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
     * @param string   $source
     * @param string   $dest
     *
     * @throws ServerException
     *
     * @return RedisResult|null
     */
    public function rpoplpush(string $source, string $dest): ?RedisResult;

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
