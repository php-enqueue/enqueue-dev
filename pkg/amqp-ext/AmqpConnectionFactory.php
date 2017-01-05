<?php

namespace Enqueue\AmqpExt;

use Enqueue\Psr\ConnectionFactory;

class AmqpConnectionFactory implements ConnectionFactory
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
     * $config = [
     *     'host'  => amqp.host The host to connect too. Note: Max 1024 characters.
     *     'port'  => amqp.port Port on the host.
     *     'vhost' => amqp.vhost The virtual host on the host. Note: Max 128 characters.
     *     'login' => amqp.login The login name to use. Note: Max 128 characters.
     *     'password' => amqp.password Password. Note: Max 128 characters.
     *     'read_timeout'  => Timeout in for income activity. Note: 0 or greater seconds. May be fractional.
     *     'write_timeout' => Timeout in for outcome activity. Note: 0 or greater seconds. May be fractional.
     *     'connect_timeout' => Connection timeout. Note: 0 or greater seconds. May be fractional.
     *     'persisted' => bool
     * ].
     *
     * @param $config
     */
    public function __construct(array $config)
    {
        $this->config = array_replace([
            'host' => null,
            'port' => null,
            'vhost' => null,
            'login' => null,
            'password' => null,
            'read_timeout' => null,
            'write_timeout' => null,
            'connect_timeout' => null,
            'persisted' => false,
        ], $config);
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpContext
     */
    public function createContext()
    {
        if (false == $this->connection) {
            $this->connection = new \AMQPConnection($this->config);

            $this->config['persisted'] ? $this->connection->pconnect() : $this->connection->connect();
        }

        if (false == $this->connection->isConnected()) {
            $this->config['persisted'] ? $this->connection->preconnect() : $this->connection->reconnect();
        }

        return new AmqpContext(new \AMQPChannel($this->connection));
    }
}
