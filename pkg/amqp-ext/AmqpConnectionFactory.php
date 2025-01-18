<?php

namespace Enqueue\AmqpExt;

use Enqueue\AmqpTools\ConnectionConfig;
use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\DelayStrategyAwareTrait;
use Enqueue\AmqpTools\RabbitMqDlxDelayStrategy;
use Interop\Amqp\AmqpConnectionFactory as InteropAmqpConnectionFactory;
use Interop\Queue\Context;

class AmqpConnectionFactory implements InteropAmqpConnectionFactory, DelayStrategyAware
{
    use DelayStrategyAwareTrait;

    /**
     * @var ConnectionConfig
     */
    private $config;

    /**
     * @var \AMQPConnection
     */
    private $connection;

    /**
     * @see ConnectionConfig for possible config formats and values
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'amqp:')
    {
        $this->config = (new ConnectionConfig($config))
            ->addSupportedScheme('amqp+ext')
            ->addSupportedScheme('amqps+ext')
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
        if ($this->config->isLazy()) {
            $context = new AmqpContext(function () {
                $extContext = $this->createExtContext($this->establishConnection());
                $extContext->qos($this->config->getQosPrefetchSize(), $this->config->getQosPrefetchCount());

                return $extContext;
            });
            $context->setDelayStrategy($this->delayStrategy);

            return $context;
        }

        $context = new AmqpContext($this->createExtContext($this->establishConnection()));
        $context->setDelayStrategy($this->delayStrategy);
        $context->setQos($this->config->getQosPrefetchSize(), $this->config->getQosPrefetchCount(), $this->config->isQosGlobal());

        return $context;
    }

    public function getConfig(): ConnectionConfig
    {
        return $this->config;
    }

    private function createExtContext(\AMQPConnection $extConnection): \AMQPChannel
    {
        return new \AMQPChannel($extConnection);
    }

    private function establishConnection(): \AMQPConnection
    {
        if (false == $this->connection) {
            $extConfig = [];
            $extConfig['host'] = $this->config->getHost();
            $extConfig['port'] = $this->config->getPort();
            $extConfig['vhost'] = $this->config->getVHost();
            $extConfig['login'] = $this->config->getUser();
            $extConfig['password'] = $this->config->getPass();
            $extConfig['read_timeout'] = $this->config->getReadTimeout();
            $extConfig['write_timeout'] = $this->config->getWriteTimeout();
            $extConfig['connect_timeout'] = $this->config->getConnectionTimeout();
            $extConfig['heartbeat'] = $this->config->getHeartbeat();

            if ($this->config->isSslOn()) {
                $extConfig['verify'] = $this->config->isSslVerify();
                $extConfig['cacert'] = $this->config->getSslCaCert();
                $extConfig['cert'] = $this->config->getSslCert();
                $extConfig['key'] = $this->config->getSslKey();
            }

            $this->connection = new \AMQPConnection($extConfig);

            $this->config->isPersisted() ?
                $this->connection->pconnect() :
                $this->connection->connect()
            ;
        }

        if (false == $this->connection->isConnected()) {
            $this->config->isPersisted() ?
                $this->connection->preconnect() :
                $this->connection->reconnect()
            ;
        }

        return $this->connection;
    }
}
