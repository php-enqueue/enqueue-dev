<?php

namespace Enqueue\AmqpBunny;

use Bunny\Client;
use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\DelayStrategyAwareTrait;
use Interop\Amqp\AmqpConnectionFactory as InteropAmqpConnectionFactory;

class AmqpConnectionFactory implements InteropAmqpConnectionFactory, DelayStrategyAware
{
    use DelayStrategyAwareTrait;

    /**
     * @var array
     */
    private $config;

    /**
     * @var Client
     */
    private $client;

    /**
     * The config could be an array, string DSN or null. In case of null it will attempt to connect to localhost with default credentials.
     *
     * [
     *     'host'  => 'amqp.host The host to connect too. Note: Max 1024 characters.',
     *     'port'  => 'amqp.port Port on the host.',
     *     'vhost' => 'amqp.vhost The virtual host on the host. Note: Max 128 characters.',
     *     'user' => 'amqp.user The user name to use. Note: Max 128 characters.',
     *     'pass' => 'amqp.password Password. Note: Max 128 characters.',
     *     'lazy' => 'the connection will be performed as later as possible, if the option set to true',
     *     'receive_method' => 'Could be either basic_get or basic_consume',
     *     'qos_prefetch_size' => 'The server will send a message in advance if it is equal to or smaller in size than the available prefetch size. May be set to zero, meaning "no specific limit"',
     *     'qos_prefetch_count' => 'Specifies a prefetch window in terms of whole messages.',
     *     'qos_global' => 'If "false" the QoS settings apply to the current channel only. If this field is "true", they are applied to the entire connection.',
     * ]
     *
     * or
     *
     * amqp://user:pass@host:10000/vhost?lazy=true&socket=true
     *
     * @param array|string $config
     */
    public function __construct($config = 'amqp://')
    {
        if (is_string($config) && 0 === strpos($config, 'amqp+bunny:')) {
            $config = str_replace('amqp+bunny:', 'amqp:', $config);
        }

        // third argument is deprecated will be removed in 0.8
        if (empty($config) || 'amqp:' === $config || 'amqp://' === $config) {
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
    }

    /**
     * @return AmqpContext
     */
    public function createContext()
    {
        if ($this->config['lazy']) {
            $context = new AmqpContext(function () {
                return $this->establishConnection()->channel();
            }, $this->config);
            $context->setDelayStrategy($this->delayStrategy);

            return $context;
        }

        $context = new AmqpContext($this->establishConnection()->channel(), $this->config);
        $context->setDelayStrategy($this->delayStrategy);

        return $context;
    }

    /**
     * @return Client
     */
    private function establishConnection()
    {
        if (false == $this->client) {
            $this->client = new Client($this->config);
            $this->client->connect();
        }

        return $this->client;
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

        $dsnConfig = array_map(function ($value) {
            return urldecode($value);
        }, $dsnConfig);

        return $dsnConfig;
    }

    /**
     * @return array
     */
    private function defaultConfig()
    {
        return [
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'lazy' => true,
            'vhost' => '/',
            'heartbeat' => 0,
            'receive_method' => 'basic_get',
            'qos_prefetch_size' => 0,
            'qos_prefetch_count' => 1,
            'qos_global' => false,
        ];
    }
}
