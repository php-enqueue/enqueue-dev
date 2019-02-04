<?php

declare(strict_types=1);

namespace Enqueue\AmqpLib;

use Enqueue\AmqpTools\ConnectionConfig;
use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\DelayStrategyAwareTrait;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Interop\Amqp\AmqpConnectionFactory as InteropAmqpConnectionFactory;
use Interop\Queue\Context;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Connection\AMQPLazyConnection;
use PhpAmqpLib\Connection\AMQPLazySocketConnection;
use PhpAmqpLib\Connection\AMQPSocketConnection;
use PhpAmqpLib\Connection\AMQPSSLConnection;
use PhpAmqpLib\Connection\AMQPStreamConnection;

class AmqpConnectionFactory implements InteropAmqpConnectionFactory, DelayStrategyAware
{
    use DelayStrategyAwareTrait;

    /**
     * @var ConnectionConfig
     */
    private $config;

    /**
     * @var AbstractConnection
     */
    private $connection;

    /**
     * @see \Enqueue\AmqpTools\ConnectionConfig for possible config formats and values.
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'amqp:')
    {
        $this->config = (new ConnectionConfig($config))
            ->addSupportedScheme('amqp+lib')
            ->addSupportedScheme('amqps+lib')
            ->addDefaultOption('stream', true)
            ->addDefaultOption('insist', false)
            ->addDefaultOption('login_method', 'AMQPLAIN')
            ->addDefaultOption('login_response', null)
            ->addDefaultOption('locale', 'en_US')
            ->addDefaultOption('keepalive', false)
            ->addDefaultOption('channel_rpc_timeout', 0.)
            ->addDefaultOption('heartbeat_on_tick', true)
            ->parse()
        ;

        if (in_array('rabbitmq', $this->config->getSchemeExtensions(), true)) {
            $this->setDelayStrategy(new RabbitMqDlxDelayStrategy());
        }
    }

    /**
     * @return AmqpContext
     */
    public function createContext(): Context
    {
        $context = new AmqpContext($this->establishConnection(), $this->config->getConfig());
        $context->setDelayStrategy($this->delayStrategy);

        return $context;
    }

    public function getConfig(): ConnectionConfig
    {
        return $this->config;
    }

    private function establishConnection(): AbstractConnection
    {
        if (false == $this->connection) {
            if ($this->config->getOption('stream')) {
                if ($this->config->isSslOn()) {
                    $sslOptions = array_filter([
                        'cafile' => $this->config->getSslCaCert(),
                        'local_cert' => $this->config->getSslCert(),
                        'local_pk' => $this->config->getSslKey(),
                        'verify_peer' => $this->config->isSslVerify(),
                        'verify_peer_name' => $this->config->isSslVerify(),
                        'passphrase' => $this->getConfig()->getSslPassPhrase(),
                        'ciphers' => $this->config->getOption('ciphers', ''),
                    ], function ($value) { return '' !== $value; });

                    $con = new AMQPSSLConnection(
                        $this->config->getHost(),
                        $this->config->getPort(),
                        $this->config->getUser(),
                        $this->config->getPass(),
                        $this->config->getVHost(),
                        $sslOptions,
                        [
                            'insist' => $this->config->getOption('insist'),
                            'login_method' => $this->config->getOption('login_method'),
                            'login_response' => $this->config->getOption('login_response'),
                            'locale' => $this->config->getOption('locale'),
                            'connection_timeout' => $this->config->getConnectionTimeout(),
                            'read_write_timeout' => (int) round(min($this->config->getReadTimeout(), $this->config->getWriteTimeout())),
                            'keepalive' => $this->config->getOption('keepalive'),
                            'heartbeat' => (int) round($this->config->getHeartbeat()),
                        ]
                    );
                } elseif ($this->config->isLazy()) {
                    $con = new AMQPLazyConnection(
                        $this->config->getHost(),
                        $this->config->getPort(),
                        $this->config->getUser(),
                        $this->config->getPass(),
                        $this->config->getVHost(),
                        $this->config->getOption('insist'),
                        $this->config->getOption('login_method'),
                        $this->config->getOption('login_response'),
                        $this->config->getOption('locale'),
                        $this->config->getConnectionTimeout(),
                        (int) round(min($this->config->getReadTimeout(), $this->config->getWriteTimeout())),
                        null,
                        $this->config->getOption('keepalive'),
                        (int) round($this->config->getHeartbeat()),
                        $this->config->getOption('channel_rpc_timeout')
                    );
                } else {
                    $con = new AMQPStreamConnection(
                        $this->config->getHost(),
                        $this->config->getPort(),
                        $this->config->getUser(),
                        $this->config->getPass(),
                        $this->config->getVHost(),
                        $this->config->getOption('insist'),
                        $this->config->getOption('login_method'),
                        $this->config->getOption('login_response'),
                        $this->config->getOption('locale'),
                        $this->config->getConnectionTimeout(),
                        (int) round(min($this->config->getReadTimeout(), $this->config->getWriteTimeout())),
                        null,
                        $this->config->getOption('keepalive'),
                        (int) round($this->config->getHeartbeat()),
                        $this->config->getOption('channel_rpc_timeout')
                    );
                }
            } else {
                if ($this->config->isSslOn()) {
                    throw new \LogicException('The socket connection implementation does not support ssl connections.');
                }

                if ($this->config->isLazy()) {
                    $con = new AMQPLazySocketConnection(
                        $this->config->getHost(),
                        $this->config->getPort(),
                        $this->config->getUser(),
                        $this->config->getPass(),
                        $this->config->getVHost(),
                        $this->config->getOption('insist'),
                        $this->config->getOption('login_method'),
                        $this->config->getOption('login_response'),
                        $this->config->getOption('locale'),
                        (int) round($this->config->getReadTimeout()),
                        $this->config->getOption('keepalive'),
                        (int) round($this->config->getWriteTimeout()),
                        (int) round($this->config->getHeartbeat()),
                        $this->config->getOption('channel_rpc_timeout')
                    );
                } else {
                    $con = new AMQPSocketConnection(
                        $this->config->getHost(),
                        $this->config->getPort(),
                        $this->config->getUser(),
                        $this->config->getPass(),
                        $this->config->getVHost(),
                        $this->config->getOption('insist'),
                        $this->config->getOption('login_method'),
                        $this->config->getOption('login_response'),
                        $this->config->getOption('locale'),
                        (int) round($this->config->getReadTimeout()),
                        $this->config->getOption('keepalive'),
                        (int) round($this->config->getWriteTimeout()),
                        (int) round($this->config->getHeartbeat()),
                        $this->config->getOption('channel_rpc_timeout')
                    );
                }
            }

            $this->connection = $con;
        }

        return $this->connection;
    }
}
