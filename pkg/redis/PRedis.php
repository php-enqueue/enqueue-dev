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
        $this->options = $config['predis_options'];

        $this->parameters = [
            'scheme' => $config['scheme'],
            'host' => $config['host'],
            'port' => $config['port'],
            'password' => $config['password'],
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

    public function lpush(string $key, string $value): int
    {
        try {
            return $this->redis->lpush($key, [$value]);
        } catch (PRedisServerException $e) {
            throw new ServerException('lpush command has failed', null, $e);
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
            throw new ServerException('brpop command has failed', null, $e);
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
            throw new ServerException('rpop command has failed', null, $e);
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
