<?php

namespace Enqueue\AmqpExt;

use Enqueue\AmqpTools\ConnectionConfig;
use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\DelayStrategyAwareTrait;
use Interop\Amqp\AmqpConnectionFactory as InteropAmqpConnectionFactory;

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
            ->addSupportedScheme('amqp+ext')
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

        if ('basic_consume' == $this->config->getOption('receive_method')) {
            if (false == (version_compare(phpversion('amqp'), '1.9.1', '>=') || '1.9.1-dev' == phpversion('amqp'))) {
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
        if ($this->config->isLazy()) {
            $context = new AmqpContext(function () {
                $extContext = $this->createExtContext($this->establishConnection());
                $extContext->qos($this->config->getQosPrefetchSize(), $this->config->getQosPrefetchCount());

                return $extContext;
            }, $this->config->getOption('receive_method'));
            $context->setDelayStrategy($this->delayStrategy);

            return $context;
        }

        $context = new AmqpContext($this->createExtContext($this->establishConnection()), $this->config['receive_method']);
        $context->setDelayStrategy($this->delayStrategy);
        $context->setQos($this->config->getQosPrefetchSize(), $this->config->getQosPrefetchCount(), $this->config->isQosGlobal());

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
     * @param \AMQPConnection $extConnection
     *
     * @return \AMQPChannel
     */
    private function createExtContext(\AMQPConnection $extConnection)
    {
        return new \AMQPChannel($extConnection);
    }

    /**
     * @return \AMQPConnection
     */
    private function establishConnection()
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

            $this->connection = new \AMQPConnection($extConfig);

            $this->config->isPersisted() ? $this->connection->pconnect() : $this->connection->connect();
        }
        if (false == $this->connection->isConnected()) {
            $this->config->isPersisted() ? $this->connection->preconnect() : $this->connection->reconnect();
        }

        return $this->connection;
    }
}
