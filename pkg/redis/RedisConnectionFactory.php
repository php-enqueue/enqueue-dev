<?php

declare(strict_types=1);

namespace Enqueue\Redis;

use Interop\Queue\PsrConnectionFactory;
use Interop\Queue\PsrContext;

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
     *   user - The user name to use.
     *   pass - Password.
     * ].
     *
     * or
     *
     * redis:
     * redis:?vendor=predis
     *
     * or
     *
     * instance of Enqueue\Redis
     *
     * @param array|string|Redis|null $config
     */
    public function __construct($config = 'redis:')
    {
        if ($config instanceof  Redis) {
            $this->redis = $config;
            $this->config = $this->defaultConfig();

            return;
        }

        if (empty($config) || 'redis:' === $config) {
            $config = [];
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $this->config = array_replace($this->defaultConfig(), $config);
        $vendor = $this->config['vendor'];

        $supportedVendors = ['predis', 'phpredis', 'custom'];
        if (false == in_array($vendor, $supportedVendors, true)) {
            throw new \LogicException(sprintf(
                'Unsupported redis vendor given. It must be either "%s". Got "%s"',
                implode('", "', $supportedVendors),
                $vendor
            ));
        }
    }

    /**
     * @return RedisContext
     */
    public function createContext(): PsrContext
    {
        if ($this->config['lazy']) {
            return new RedisContext(function () {
                return $this->createRedis();
            });
        }

        return new RedisContext($this->createRedis());
    }

    private function createRedis(): Redis
    {
        if (false == $this->redis) {
            if ('phpredis' == $this->config['vendor'] && false == $this->redis) {
                $this->redis = new PhpRedis($this->config);
            }

            if ('predis' == $this->config['vendor'] && false == $this->redis) {
                $this->redis = new PRedis($this->config);
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

    private function parseDsn(string $dsn): array
    {
        $unsupportedError = 'The given DSN "%s" is not supported. Must start with "redis:".';

        if ((false === strpos($dsn, 'redis:')) and (false === strpos($dsn, 'rediss:'))) {
            throw new \LogicException(sprintf($unsupportedError, $dsn));
        }

        if (false === $config = parse_url($dsn)) {
            throw new \LogicException(sprintf('Failed to parse DSN "%s"', $dsn));
        }

        if (array_key_exists('port', $config)) {
            $config['port'] = (int) $config['port'];
        }

        if ($query = parse_url($dsn, PHP_URL_QUERY)) {
            $queryConfig = [];
            parse_str($query, $queryConfig);

            $config = array_replace($queryConfig, $config);
        }

        if (isset($config['vendor'])) {
            $vendor = $config['vendor'];
        } else {
            $vendor = "";
        }


        //predis additionaly supports tls as scheme, but it must remain in the $config array
        if ($vendor!='predis') {
            if ($config['scheme']!='redis') throw new \LogicException(sprintf($unsupportedError, $dsn));
            unset($config['scheme']);
        }
        unset($config['query']);

        $config['lazy'] = empty($config['lazy']) ? false : true;
        $config['persisted'] = empty($config['persisted']) ? false : true;

        return $config;
    }

    private function defaultConfig(): array
    {
        return [
            'host' => 'localhost',
            'port' => 6379,
            'timeout' => .0,
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
