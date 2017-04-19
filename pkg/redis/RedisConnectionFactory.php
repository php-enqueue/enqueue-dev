<?php

namespace Enqueue\Redis;

use Enqueue\Psr\PsrConnectionFactory;
use Predis\Client;

class RedisConnectionFactory implements PsrConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var \Redis
     */
    private $redis;

    /**
     * $config = [
     *  'host' => can be a host, or the path to a unix domain socket
     *  'port' => optional
     *  'timeout' => value in seconds (optional, default is 0.0 meaning unlimited)
     *  'reserved' => should be null if $retry_interval is specified
     *  'retry_interval' => retry interval in milliseconds.
     *  'vendor' => 'The library used internally to interact with Redis server
     *  'persisted' => bool, Whether it use single persisted connection or open a new one for every context
     *  'lazy' => the connection will be performed as later as possible, if the option set to true
     * ].
     *
     * @param $config
     */
    public function __construct(array $config)
    {
        $this->config = array_replace([
            'host' => null,
            'port' => null,
            'timeout' => null,
            'reserved' => null,
            'retry_interval' => null,
            'vendor' => 'phpredis',
            'persisted' => false,
            'lazy' => true,
        ], $config);

        $supportedVendors = ['predis', 'phpredis'];
        if (false == in_array($this->config['vendor'], $supportedVendors)) {
            throw new \LogicException(sprintf(
                'Unsupported redis vendor given. It must be either "%s". Got "%s"',
                implode('", "', $supportedVendors),
                $this->config['vendor']
            ));
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return RedisContext
     */
    public function createContext()
    {
        if ($this->config['lazy']) {
            return new RedisContext(function () {
                $redis = $this->createRedis();
                $redis->connect();

                return $redis;
            });
        }

        return new RedisContext($this->createRedis());
    }

    /**
     * @return Redis
     */
    private function createRedis()
    {
        if ('phpredis' == $this->config['vendor'] && false == $this->redis) {
            $this->redis = new PhpRedis(new \Redis(), $this->config);
        }

        if ('predis' == $this->config['vendor'] && false == $this->redis) {
            $this->redis = new PRedis(new Client($this->config, ['exceptions' => true]));
        }

        return $this->redis;
    }
}
