<?php
namespace Enqueue\Redis;

use Predis\ClientInterface;
use Predis\Response\ServerException as PRedisServerException;

class PRedis implements Redis
{
    /**
     * @var ClientInterface
     */
    private $redis;

    /**
     * @param ClientInterface $redis
     */
    public function __construct(ClientInterface $redis)
    {
        $this->redis = $redis;
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
            return $this->brpop($key, (int) $timeout / 1000);
        } catch (PRedisServerException $e) {
            throw new ServerException('brpop command has failed', null, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rpop($key)
    {   try {
           return $this->rpop($key);
        } catch (PRedisServerException $e) {
            throw new ServerException('rpop command has failed', null, $e);
        }
    }

    public function connect()
    {
        $this->redis->connect();
    }

    public function disconnect()
    {
        $this->redis->disconnect();
    }
}
