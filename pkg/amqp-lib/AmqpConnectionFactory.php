<?php

namespace Enqueue\AmqpLib;

use Interop\Queue\PsrConnectionFactory;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class AmqpConnectionFactory implements PsrConnectionFactory
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var AbstractConnection
     */
    private $connection;

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = array_replace($this->defaultConfig(), $config);
    }

    /**
     * @return AmqpContext
     */
    public function createContext()
    {
        return new AmqpContext($this->establishConnection());
    }

    /**
     * @return AbstractConnection
     */
    private function establishConnection()
    {
        if (false == $this->connection) {
            $this->connection = new AMQPStreamConnection(
                $this->config['host'],
                $this->config['port'],
                $this->config['user'],
                $this->config['pass'],
                $this->config['vhost']
            );
        }

        return $this->connection;
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
        ];
    }
}
