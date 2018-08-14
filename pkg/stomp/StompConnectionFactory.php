<?php

namespace Enqueue\Stomp;

use Interop\Queue\PsrConnectionFactory;
use Stomp\Network\Connection;

class StompConnectionFactory implements PsrConnectionFactory
{
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
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $this->config = array_replace($this->defaultConfig(), $config);
    }

    /**
     * {@inheritdoc}
     *
     * @return StompContext
     */
    public function createContext()
    {
        if ($this->config['lazy']) {
            return new StompContext(function () {
                return $this->establishConnection();
            });
        }

        return new StompContext($this->establishConnection());
    }

    /**
     * @return BufferedStompClient
     */
    private function establishConnection()
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

    /**
     * @param string $dsn
     *
     * @return array
     */
    private function parseDsn($dsn)
    {
        if (false === strpos($dsn, 'stomp:')) {
            throw new \LogicException(sprintf('The given DSN "%s" is not supported. Must start with "stomp:".', $dsn));
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

        $config['sync'] = empty($config['sync']) ? false : true;
        $config['lazy'] = empty($config['lazy']) ? false : true;

        return $config;
    }

    /**
     * @return array
     */
    private function defaultConfig()
    {
        return [
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
