<?php

namespace Enqueue\AmqpLib;

use Enqueue\AmqpTools\ConnectionConfig;
use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\DelayStrategyAwareTrait;
use Interop\Amqp\AmqpConnectionFactory as InteropAmqpConnectionFactory;
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
     * @see \Enqueue\AmqpTools\ConnectionConfig for possible config formats and values
     *
     * In addition this factory accepts next options:
     *   receive_method - Could be either basic_get or basic_consume
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
            ->addDefaultOption('receive_method', 'basic_get')
            ->parse()
        ;

        $supportedMethods = ['basic_get', 'basic_consume'];
        if (false == in_array($this->config->getOption('receive_method'), $supportedMethods, true)) {
            throw new \LogicException(sprintf(
                'Invalid "receive_method" option value "%s". It could be only "%s"',
                $this->config->getOption('receive_method'),
                implode('", "', $supportedMethods)
            ));
        }
    }

    /**
     * @return AmqpContext
     */
    public function createContext()
    {
        $context = new AmqpContext($this->establishConnection(), $this->config->getConfig());
        $context->setDelayStrategy($this->delayStrategy);

        return $context;
    }

    /**
     * @return ConnectionConfig
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return AbstractConnection
     */
    private function establishConnection()
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
                        (int) round($this->config->getHeartbeat())
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
                        (int) round($this->config->getHeartbeat())
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
                        (int) round($this->config->getHeartbeat())
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
                        (int) round($this->config->getHeartbeat())
                    );
                }
            }

            $this->connection = $con;
        }

        return $this->connection;
    }
}
