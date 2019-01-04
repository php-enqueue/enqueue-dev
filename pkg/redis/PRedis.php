<?php

declare(strict_types=1);

namespace Enqueue\Redis;

use Predis\Client;
use Predis\ClientInterface;
use Predis\Response\ServerException as PRedisServerException;

class PRedis implements Redis
{
    /**
     * @var array
     */
    private $parameters;

    /**
     * @var array
     */
    private $options;

    /**
     * @var ClientInterface
     */
    private $redis;

    /**
     * @see https://github.com/nrk/predis/wiki/Client-Options
     */
    public function __construct(array $config)
    {
        if (false == class_exists(Client::class)) {
            throw new \LogicException('The package "predis/predis" must be installed. Please run "composer req predis/predis:^1.1" to install it');
        }

        $this->options = $config['predis_options'];

        $this->parameters = [
            'scheme' => $config['scheme'],
            'host' => $config['host'],
            'port' => $config['port'],
            'password' => $config['password'],
            'database' => $config['database'],
            'path' => $config['path'],
            'async' => $config['async'],
            'persistent' => $config['persistent'],
            'timeout' => $config['timeout'],
            'read_write_timeout' => $config['read_write_timeout'],
        ];

        if ($config['ssl']) {
            $this->parameters['ssl'] = $config['ssl'];
        }
    }

    public function eval(string $script, array $keys = [], array $args = [])
    {
        try {
            // mixed eval($script, $numkeys, $keyOrArg1 = null, $keyOrArgN = null)
            return call_user_func_array([$this->redis, 'eval'], array_merge([$script, count($keys)], $keys, $args));
        } catch (PRedisServerException $e) {
            throw new ServerException('eval command has failed', 0, $e);
        }
    }

    public function zadd(string $key, string $value, float $score): int
    {
        try {
            return $this->redis->zadd($key, [$value => $score]);
        } catch (PRedisServerException $e) {
            throw new ServerException('zadd command has failed', 0, $e);
        }
    }

    public function zrem(string $key, string $value): int
    {
        try {
            return $this->redis->zrem($key, [$value]);
        } catch (PRedisServerException $e) {
            throw new ServerException('zrem command has failed', 0, $e);
        }
    }

    public function lpush(string $key, string $value): int
    {
        try {
            return $this->redis->lpush($key, [$value]);
        } catch (PRedisServerException $e) {
            throw new ServerException('lpush command has failed', 0, $e);
        }
    }

    public function brpop(array $keys, int $timeout): ?RedisResult
    {
        try {
            if ($result = $this->redis->brpop($keys, $timeout)) {
                return new RedisResult($result[0], $result[1]);
            }

            return null;
        } catch (PRedisServerException $e) {
            throw new ServerException('brpop command has failed', 0, $e);
        }
    }

    public function rpop(string $key): ?RedisResult
    {
        try {
            if ($message = $this->redis->rpop($key)) {
                return new RedisResult($key, $message);
            }

            return null;
        } catch (PRedisServerException $e) {
            throw new ServerException('rpop command has failed', 0, $e);
        }
    }

    public function connect(): void
    {
        if ($this->redis) {
            return;
        }

        $this->redis = new Client($this->parameters, $this->options);

        // No need to pass "auth" here because Predis already handles this internally

        $this->redis->connect();
    }

    public function disconnect(): void
    {
        $this->redis->disconnect();
    }

    public function del(string $key): void
    {
        $this->redis->del([$key]);
    }
}
