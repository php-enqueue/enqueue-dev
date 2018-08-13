<?php

namespace Enqueue\Redis;

class PhpRedis implements Redis
{
    /**
     * @var \Redis
     */
    private $redis;

    /**
     * @var array
     */
    private $config;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = array_replace([
            'host' => null,
            'port' => null,
            'pass' => null,
            'user' => null,
            'timeout' => .0,
            'reserved' => null,
            'retry_interval' => null,
            'persisted' => false,
            'database' => 0,
        ], $config);
    }

    /**
     * {@inheritdoc}
     */
    public function lpush(string $key, string $value): int
    {
        return $this->redis->lPush($key, $value);
    }

    /**
     * {@inheritdoc}
     */
    public function brpop(array $keys, int $timeout): ?RedisResult
    {
        if ($result = $this->redis->brPop($keys, $timeout)) {
            return new RedisResult($result[0], $result[1]);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function rpop(string $key): ?RedisResult
    {
        if ($message = $this->redis->rPop($key)) {
            return new RedisResult($key, $message);
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function connect(): void
    {
        if ($this->redis) {
            return;
        }

        $this->redis = new \Redis();

        if ($this->config['persisted']) {
            $this->redis->pconnect(
                $this->config['host'],
                $this->config['port'],
                $this->config['timeout']
            );
        } else {
            $this->redis->connect(
                $this->config['host'],
                $this->config['port'],
                $this->config['timeout'],
                $this->config['reserved'],
                $this->config['retry_interval']
            );
        }

        if ($this->config['pass']) {
            $this->redis->auth($this->config['pass']);
        }

        $this->redis->select($this->config['database']);
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect(): void
    {
        if ($this->redis) {
            $this->redis->close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function del(string $key): void
    {
        $this->redis->del($key);
    }
}
