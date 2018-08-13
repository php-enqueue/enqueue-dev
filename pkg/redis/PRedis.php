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
    }

    /**
     * {@inheritdoc}
     */
    public function lpush($key, $value)
    {
        try {
            $this->redis->lpush($key, [$value]);
        } catch (PRedisServerException $e) {
            throw new ServerException('lpush command has failed', null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function brpop($key, $timeout)
    {
        try {
            if ($result = $this->redis->brpop([$key], $timeout)) {
                return $result[1];
            }
        } catch (PRedisServerException $e) {
            throw new ServerException('brpop command has failed', null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rpop($key)
    {
        try {
            return $this->redis->rpop($key);
        } catch (PRedisServerException $e) {
            throw new ServerException('rpop command has failed', null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        $this->redis = new Client($this->config, ['exceptions' => true]);

        if ($this->config['pass']) {
            $this->redis->auth($this->config['pass']);
        }

        $this->redis->connect();
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        $this->redis->disconnect();
    }

    /**
     * {@inheritdoc}
     */
    public function del($key)
    {
        $this->redis->del([$key]);
    }
}
