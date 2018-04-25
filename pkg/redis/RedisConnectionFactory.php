<?php

namespace Enqueue\Redis;

use Interop\Queue\PsrConnectionFactory;
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
     *  'redis' => 'Used only if vendor is custom, should contain an instance of \Enqueue\Redis\Redis interface.
     *  'persisted' => bool, Whether it use single persisted connection or open a new one for every context
     *  'lazy' => the connection will be performed as later as possible, if the option set to true
     *  'database' => Database index to select when connected (default value: 0)
     * ].
     *
     * or
     *
     * redis:
     * redis:?vendor=predis
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'redis:')
    {
        if (empty($config) || 'redis:' === $config) {
            $config = [];
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $this->config = array_replace($this->defaultConfig(), $config);

        $supportedVendors = ['predis', 'phpredis', 'custom'];
        if (false == in_array($this->config['vendor'], $supportedVendors, true)) {
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
                return $this->createRedis();
            });
        }

        return new RedisContext($this->createRedis());
    }

    /**
     * @return Redis
     */
    private function createRedis()
    {
        if (false == $this->redis) {
            if ('phpredis' == $this->config['vendor'] && false == $this->redis) {
                $this->redis = new PhpRedis($this->config);
            }

            if ('predis' == $this->config['vendor'] && false == $this->redis) {
                $this->redis = new PRedis(new Client($this->config, ['exceptions' => true]));
            }

            if ('custom' == $this->config['vendor'] && false == $this->redis) {
                if (empty($this->config['redis'])) {
                    throw new \LogicException('The redis option should be set if vendor is custom.');
                }

                if (false == $this->config['redis'] instanceof Redis) {
                    throw new \LogicException(sprintf('The redis option should be instance of "%s".', Redis::class));
                }

                $this->redis = $this->config['redis'];
            }

            $this->redis->connect();
        }

        return $this->redis;
    }

    /**
     * @param string $dsn
     *
     * @return array
     */
    private function parseDsn($dsn)
    {
        if (false === strpos($dsn, 'redis:')) {
            throw new \LogicException(sprintf('The given DSN "%s" is not supported. Must start with "redis:".', $dsn));
        }

        if (false === $config = parse_url($dsn)) {
            throw new \LogicException(sprintf('Failed to parse DSN "%s"', $dsn));
        }

        if ($query = parse_url($dsn, PHP_URL_QUERY)) {
            $queryConfig = [];
            parse_str($query, $queryConfig);

            $config = array_replace($queryConfig, $config);
        }

        unset($config['query'], $config['scheme']);

        $config['lazy'] = empty($config['lazy']) ? false : true;
        $config['persisted'] = empty($config['persisted']) ? false : true;

        return $config;
    }

    /**
     * @return array
     */
    private function defaultConfig()
    {
        return [
            'host' => 'localhost',
            'port' => 6379,
            'timeout' => null,
            'reserved' => null,
            'retry_interval' => null,
            'vendor' => 'phpredis',
            'redis' => null,
            'persisted' => false,
            'lazy' => true,
            'database' => 0,
        ];
    }
}
