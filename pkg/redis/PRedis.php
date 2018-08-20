<?php

namespace Enqueue\Redis;

use Predis\Client;
use Predis\ClientInterface;
use Predis\Response\ServerException as PRedisServerException;

class PRedis implements Redis
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var ClientInterface
     */
    private $redis;

    /**
     * @param ClientInterface $redis
     */
    public function __construct(array $config)
    {
        $this->config = $this->config = array_replace([
            'host' => null,
            'port' => null,
            'pass' => null,
            'user' => null,
            'timeout' => null,
            'reserved' => null,
            'retry_interval' => null,
            'persisted' => false,
            'database' => 0,
        ], $config);

        // Predis client wants the key to be named "password"
        $this->config['password'] = $this->config['pass'];
        unset($this->config['pass']);
    }

    /**
     * {@inheritdoc}
     */
    public function lpush(string $key, string $value): int
    {
        try {
            return $this->redis->lpush($key, [$value]);
        } catch (PRedisServerException $e) {
            throw new ServerException('lpush command has failed', null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function brpop(array $keys, int $timeout): ?RedisResult
    {
        try {
            if ($result = $this->redis->brpop($keys, $timeout)) {
                return new RedisResult($result[0], $result[1]);
            }

            return null;
        } catch (PRedisServerException $e) {
            throw new ServerException('brpop command has failed', null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rpop(string $key): ?RedisResult
    {
        try {
            if ($message = $this->redis->rpop($key)) {
                return new RedisResult($key, $message);
            }

            return null;
        } catch (PRedisServerException $e) {
            throw new ServerException('rpop command has failed', null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function connect(): void
    {
        if ($this->redis) {
            return;
        }

        $this->redis = new Client($this->config, ['exceptions' => true]);

        // No need to pass "auth" here because Predis already handles
        // this internally

        $this->redis->connect();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
        $this->redis->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    public function del(string $key): void
    {
        $this->redis->del([$key]);
    }
}
