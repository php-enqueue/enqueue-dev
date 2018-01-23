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
     * @param array|\Redis $param
     */
    public function __construct($param)
    {
        if ($param instanceof \Redis) {
            $this->redis = $param;
        } else {
            $this->config = array_replace([
                'host' => null,
                'port' => null,
                'timeout' => null,
                'reserved' => null,
                'retry_interval' => null,
                'persisted' => false,
                'database' => 0,
            ], $param);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function lpush($key, $value)
    {
        if (false == $this->redis->lPush($key, $value)) {
            throw new ServerException($this->redis->getLastError());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function brpop($key, $timeout)
    {
        if ($result = $this->redis->brPop([$key], $timeout)) {
            return $result[1];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rpop($key)
    {
        return $this->redis->rPop($key);
    }

    /**
     * {@inheritdoc}
     */
    public function connect()
    {
        if (false == $this->redis) {
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

            $this->redis->select($this->config['database']);
        }

        return $this->redis;
    }

    /**
     * {@inheritdoc}
     */
    public function disconnect()
    {
        if ($this->redis) {
            $this->redis->close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function del($key)
    {
        $this->redis->del($key);
    }
}
