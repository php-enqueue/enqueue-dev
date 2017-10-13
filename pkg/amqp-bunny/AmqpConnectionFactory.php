<?php

namespace Enqueue\AmqpBunny;

use Bunny\Client;
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
     * @var Client
     */
    private $client;

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
            ->addSupportedSchemes('amqp+bunny')
            ->addDefaultOption('receive_method', 'basic_get')
            ->addDefaultOption('tcp_nodelay', null)
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
        if ($this->config->isLazy()) {
            $context = new AmqpContext(function () {
                $channel = $this->establishConnection()->channel();
                $channel->qos($this->config->getQosPrefetchSize(), $this->config->getQosPrefetchCount(), $this->config->isQosGlobal());

                return $channel;
            }, $this->config->getConfig());
            $context->setDelayStrategy($this->delayStrategy);

            return $context;
        }

        $context = new AmqpContext($this->establishConnection()->channel(), $this->config->getConfig());
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
     * @return Client
     */
    private function establishConnection()
    {
        if (false == $this->client) {
            $bunnyConfig = [];
            $bunnyConfig['host'] = $this->config->getHost();
            $bunnyConfig['port'] = $this->config->getPort();
            $bunnyConfig['vhost'] = $this->config->getVHost();
            $bunnyConfig['user'] = $this->config->getUser();
            $bunnyConfig['password'] = $this->config->getPass();
            $bunnyConfig['read_write_timeout'] = min($this->config->getReadTimeout(), $this->config->getWriteTimeout());
            $bunnyConfig['timeout'] = $this->config->getConnectionTimeout();

//            $bunnyConfig['persistent'] = $this->config->isPersisted();
//            if ($this->config->isPersisted()) {
//                $bunnyConfig['path'] = $this->config->getOption('path', $this->config->getOption('vhost'));
//            }

            if ($this->config->getHeartbeat()) {
                $bunnyConfig['heartbeat'] = $this->config->getHeartbeat();
            }

            if (null !== $this->config->getOption('tcp_nodelay')) {
                $bunnyConfig['tcp_nodelay'] = $this->config->getOption('tcp_nodelay');
            }

            $this->client = new Client($bunnyConfig);
            $this->client->connect();
        }

        return $this->client;
    }
}
