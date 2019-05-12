<?php

declare(strict_types=1);

namespace Enqueue\Stomp;

use Enqueue\Dsn\Dsn;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;
use Stomp\Network\Connection;

class StompConnectionFactory implements ConnectionFactory
{
    const SCHEME_EXT_ACTIVEMQ = 'activemq';
    const SCHEME_EXT_RABBITMQ = 'rabbitmq';

    /**
     * @var array
     */
    private $config;

    /**
     * @var BufferedStompClient
     */
    private $stomp;

    /**
     * $config = [
     * 'host' => null,
     * 'port' => null,
     * 'login' => null,
     * 'password' => null,
     * 'vhost' => null,
     * 'buffer_size' => 1000,
     * 'connection_timeout' => 1,
     * 'sync' => false,
     * 'lazy' => true,
     * 'ssl_on' => false,
     * ].
     *
     * or
     *
     * stomp:
     * stomp:?buffer_size=100
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'stomp:')
    {
        if (empty($config) || 'stomp:' === $config) {
            $config = [];
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
            if (array_key_exists('dsn', $config)) {
                $config = array_replace($config, $this->parseDsn($config['dsn']));

                unset($config['dsn']);
            }
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $this->config = array_replace($this->defaultConfig(), $config);
    }

    /**
     * @return StompContext
     */
    public function createContext(): Context
    {
        $useExchangePrefix = self::SCHEME_EXT_RABBITMQ === $this->config['target'] ? true : false;

        if ($this->config['lazy']) {
            return new StompContext(function () {
                return $this->establishConnection();
            }, $useExchangePrefix);
        }

        return new StompContext($this->establishConnection(), $useExchangePrefix);
    }

    private function establishConnection(): BufferedStompClient
    {
        if (false == $this->stomp) {
            $config = $this->config;

            $scheme = (true === $config['ssl_on']) ? 'ssl' : 'tcp';
            $uri = $scheme.'://'.$config['host'].':'.$config['port'];
            $connection = new Connection($uri, $config['connection_timeout']);

            $this->stomp = new BufferedStompClient($connection, $config['buffer_size']);
            $this->stomp->setLogin($config['login'], $config['password']);
            $this->stomp->setVhostname($config['vhost']);
            $this->stomp->setSync($config['sync']);

            $this->stomp->connect();
        }

        return $this->stomp;
    }

    private function parseDsn(string $dsn): array
    {
        $dsn = Dsn::parseFirst($dsn);

        if ('stomp' !== $dsn->getSchemeProtocol()) {
            throw new \LogicException(sprintf('The given DSN is not supported. Must start with "stomp:".'));
        }

        $schemeExtension = current($dsn->getSchemeExtensions());
        if (false === $schemeExtension) {
            $schemeExtension = self::SCHEME_EXT_RABBITMQ;
        }
        if (self::SCHEME_EXT_ACTIVEMQ !== $schemeExtension && self::SCHEME_EXT_RABBITMQ !== $schemeExtension) {
            throw new \LogicException(sprintf('The given DSN is not supported. The scheme extension "%s" provided is invalid. It must be one of "%s" or "%s".', $schemeExtension, self::SCHEME_EXT_ACTIVEMQ, self::SCHEME_EXT_RABBITMQ));
        }

        return array_filter(array_replace($dsn->getQuery(), [
            'target' => $schemeExtension,
            'host' => $dsn->getHost(),
            'port' => $dsn->getPort(),
            'login' => $dsn->getUser(),
            'password' => $dsn->getPassword(),
            'vhost' => null !== $dsn->getPath() ? ltrim($dsn->getPath(), '/') : null,
            'buffer_size' => $dsn->getDecimal('buffer_size'),
            'connection_timeout' => $dsn->getDecimal('connection_timeout'),
            'sync' => $dsn->getBool('sync'),
            'lazy' => $dsn->getBool('lazy'),
            'ssl_on' => $dsn->getBool('ssl_on'),
        ]), function ($value) { return null !== $value; });
    }

    private function defaultConfig(): array
    {
        return [
            'target' => self::SCHEME_EXT_RABBITMQ,
            'host' => 'localhost',
            'port' => 61613,
            'login' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
            'buffer_size' => 1000,
            'connection_timeout' => 1,
            'sync' => false,
            'lazy' => true,
            'ssl_on' => false,
        ];
    }
}
