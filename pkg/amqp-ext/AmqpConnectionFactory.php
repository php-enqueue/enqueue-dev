<?php

namespace Enqueue\AmqpExt;

use Interop\Amqp\AmqpConnectionFactory as InteropAmqpConnectionFactory;

class AmqpConnectionFactory implements InteropAmqpConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var \AMQPConnection
     */
    private $connection;

    /**
     * The config could be an array, string DSN or null. In case of null it will attempt to connect to localhost with default credentials.
     *
     * [
     *     'host'  => 'amqp.host The host to connect too. Note: Max 1024 characters.',
     *     'port'  => 'amqp.port Port on the host.',
     *     'vhost' => 'amqp.vhost The virtual host on the host. Note: Max 128 characters.',
     *     'user' => 'amqp.user The user name to use. Note: Max 128 characters.',
     *     'pass' => 'amqp.password Password. Note: Max 128 characters.',
     *     'read_timeout'  => 'Timeout in for income activity. Note: 0 or greater seconds. May be fractional.',
     *     'write_timeout' => 'Timeout in for outcome activity. Note: 0 or greater seconds. May be fractional.',
     *     'connect_timeout' => 'Connection timeout. Note: 0 or greater seconds. May be fractional.',
     *     'persisted' => 'bool, Whether it use single persisted connection or open a new one for every context',
     *     'lazy' => 'the connection will be performed as later as possible, if the option set to true',
     *     'pre_fetch_count' => 'Controls how many messages could be prefetched',
     *     'pre_fetch_size' => 'Controls how many messages could be prefetched',
     *     'receive_method' => 'Could be either basic_get or basic_consume',
     * ]
     *
     * or
     *
     * amqp://user:pass@host:10000/vhost?lazy=true&persisted=false&read_timeout=2
     *
     * @param array|string $config
     */
    public function __construct($config = 'amqp://')
    {
        if (empty($config) || 'amqp://' === $config) {
            $config = [];
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $this->config = array_replace($this->defaultConfig(), $config);

        $supportedMethods = ['basic_get', 'basic_consume'];
        if (false == in_array($this->config['receive_method'], $supportedMethods, true)) {
            throw new \LogicException(sprintf(
                'Invalid "receive_method" option value "%s". It could be only "%s"',
                $this->config['receive_method'],
                implode('", "', $supportedMethods)
            ));
        }

        if ('basic_consume' == $this->config['receive_method']) {
            if (false == (version_compare(phpversion('amqp'), '1.9.1', '>=') || phpversion('amqp') == '1.9.1-dev')) {
                // @see https://github.com/php-enqueue/enqueue-dev/issues/110 and https://github.com/pdezwart/php-amqp/issues/281
                throw new \LogicException('The "basic_consume" method does not work on amqp extension prior 1.9.1 version.');
            }
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpContext
     */
    public function createContext()
    {
        if ($this->config['lazy']) {
            return new AmqpContext(function () {
                return $this->createExtContext($this->establishConnection());
            }, $this->config['receive_method']);
        }

        return new AmqpContext($this->createExtContext($this->establishConnection()), $this->config['receive_method']);
    }

    /**
     * @param \AMQPConnection $extConnection
     *
     * @return \AMQPChannel
     */
    private function createExtContext(\AMQPConnection $extConnection)
    {
        $channel = new \AMQPChannel($extConnection);
        if (false == empty($this->config['pre_fetch_count'])) {
            $channel->setPrefetchCount((int) $this->config['pre_fetch_count']);
        }

        if (false == empty($this->config['pre_fetch_size'])) {
            $channel->setPrefetchSize((int) $this->config['pre_fetch_size']);
        }

        return $channel;
    }

    /**
     * @return \AMQPConnection
     */
    private function establishConnection()
    {
        if (false == $this->connection) {
            $config = $this->config;
            $config['login'] = $this->config['user'];
            $config['password'] = $this->config['pass'];

            $this->connection = new \AMQPConnection($config);

            $this->config['persisted'] ? $this->connection->pconnect() : $this->connection->connect();
        }
        if (false == $this->connection->isConnected()) {
            $this->config['persisted'] ? $this->connection->preconnect() : $this->connection->reconnect();
        }

        return $this->connection;
    }

    /**
     * @param string $dsn
     *
     * @return array
     */
    private function parseDsn($dsn)
    {
        $dsnConfig = parse_url($dsn);
        if (false === $dsnConfig) {
            throw new \LogicException(sprintf('Failed to parse DSN "%s"', $dsn));
        }

        $dsnConfig = array_replace([
            'scheme' => null,
            'host' => null,
            'port' => null,
            'user' => null,
            'pass' => null,
            'path' => null,
            'query' => null,
        ], $dsnConfig);

        if ('amqp' !== $dsnConfig['scheme']) {
            throw new \LogicException(sprintf('The given DSN scheme "%s" is not supported. Could be "amqp" only.', $dsnConfig['scheme']));
        }

        if ($dsnConfig['query']) {
            $query = [];
            parse_str($dsnConfig['query'], $query);

            $dsnConfig = array_replace($query, $dsnConfig);
        }

        $dsnConfig['vhost'] = ltrim($dsnConfig['path'], '/');

        unset($dsnConfig['scheme'], $dsnConfig['query'], $dsnConfig['fragment'], $dsnConfig['path']);

        $config = array_replace($this->defaultConfig(), $dsnConfig);
        $config = array_map(function ($value) {
            return urldecode($value);
        }, $config);

        return $config;
    }

    /**
     * @return array
     */
    private function defaultConfig()
    {
        return [
            'host' => 'localhost',
            'port' => 5672,
            'vhost' => '/',
            'user' => 'guest',
            'pass' => 'guest',
            'read_timeout' => null,
            'write_timeout' => null,
            'connect_timeout' => null,
            'persisted' => false,
            'lazy' => true,
            'pre_fetch_count' => null,
            'pre_fetch_size' => null,
            'receive_method' => 'basic_get',
        ];
    }
}
