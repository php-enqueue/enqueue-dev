<?php

declare(strict_types=1);

namespace Enqueue\Redis;

use Enqueue\Dsn\Dsn;
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
     * @see https://github.com/nrk/predis/wiki/Connection-Parameters
     *
     * $config = [
     *  'dsn' => A redis DSN string.
     *  'scheme' => Specifies the protocol used to communicate with an instance of Redis.
     *  'host' => IP or hostname of the target server.
     *  'port' => TCP/IP port of the target server.
     *  'path' => Path of the UNIX domain socket file used when connecting to Redis using UNIX domain sockets.
     *  'database' => Accepts a numeric value that is used by Predis to automatically select a logical database with the SELECT command.
     *  'password' => Accepts a value used to authenticate with a Redis server protected by password with the AUTH command.
     *  'async' => Specifies if connections to the server is estabilished in a non-blocking way (that is, the client is not blocked while the underlying resource performs the actual connection).
     *  'persistent' => Specifies if the underlying connection resource should be left open when a script ends its lifecycle.
     *  'timeout' => Timeout (expressed in seconds) used to connect to a Redis server after which an exception is thrown.
     *  'read_write_timeout' => Timeout (expressed in seconds) used when performing read or write operations on the underlying network resource after which an exception is thrown.
     *  'predis_options' => An array of predis specific options.
     *  'ssl' => could be any of http://fi2.php.net/manual/en/context.ssl.php#refsect1-context.ssl-options
     * ].
     *
     * or
     *
     * redis://h:asdfqwer1234asdf@ec2-111-1-1-1.compute-1.amazonaws.com:111
     * tls://127.0.0.1?ssl[cafile]=private.pem&ssl[verify_peer]=1
     *
     * or
     *
     * instance of Enqueue\Redis
     *
     * @param array|string|Redis|null $config
     */
    public function __construct($config = 'redis:')
    {
        if ($config instanceof Redis) {
            $this->redis = $config;
            $this->config = $this->defaultConfig();

            return;
        }

        if (empty($config)) {
            $config = [];
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
            if (array_key_exists('dsn', $config)) {
                $config = array_replace($config, $this->parseDsn($config['dsn']));

                unset($config['dsn']);
            }
        } else {
            throw new \LogicException(sprintf('The config must be either an array of options, a DSN string, null or instance of %s', Redis::class));
        }

        $this->config = array_replace($this->defaultConfig(), $config);
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
            if (in_array('predis', $this->config['scheme_extensions'], true)) {
                $this->redis = new PRedis($this->config);
            } elseif (in_array('phpredis', $this->config['scheme_extensions'], true)) {
                $this->redis = new PhpRedis($this->config);
            } else {
                $this->redis = new PRedis($this->config);
            }

            $this->redis->connect();
        }

        return $this->redis;
    }

    private function parseDsn(string $dsn): array
    {
        $dsn = new Dsn($dsn);

        $supportedSchemes = ['redis', 'rediss', 'tcp', 'tls', 'unix'];
        if (false == in_array($dsn->getSchemeProtocol(), $supportedSchemes, true)) {
            throw new \LogicException(sprintf(
                'The given scheme protocol "%s" is not supported. It must be one of "%s"',
                $dsn->getSchemeProtocol(),
                implode('", "', $supportedSchemes)
            ));
        }

        $database = null;
        if ('unix' !== $dsn->getSchemeProtocol() && $dsn->getPath()) {
            $database = ltrim($dsn->getPath());
        }

        return array_filter(array_replace($dsn->getQuery(), [
            'scheme' => $dsn->getSchemeProtocol(),
            'scheme_extensions' => $dsn->getSchemeExtensions(),
            'host' => $dsn->getHost(),
            'port' => $dsn->getPort(),
            'path' => $dsn->getPath(),
            'database' => $database,
            'password' => $dsn->getPassword(),
            'async' => $dsn->getBool('async'),
            'persistent' => $dsn->getBool('persistent'),
            'timeout' => $dsn->getFloat('timeout'),
            'read_write_timeout' => $dsn->getFloat('read_write_timeout'),
        ]), function ($value) { return null !== $value; });
    }

    private function defaultConfig(): array
    {
        return [
            'scheme' => 'redis',
            'scheme_extensions' => [],
            'host' => '127.0.0.1',
            'port' => 6379,
            'path' => null,
            'database' => null,
            'password' => null,
            'async' => false,
            'persistent' => false,
            'timeout' => 5.0,
            'read_write_timeout' => null,
            'predis_options' => null,
        ];
    }
}
