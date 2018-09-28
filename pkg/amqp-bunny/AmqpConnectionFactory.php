<?php

declare(strict_types=1);

namespace Enqueue\AmqpBunny;

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
     * @var BunnyClient
     */
    private $client;

    /**
     * @see \Enqueue\AmqpTools\ConnectionConfig for possible config formats and values
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'amqp:')
    {
        $this->config = (new ConnectionConfig($config))
            ->addSupportedScheme('amqp+bunny')
            ->addDefaultOption('tcp_nodelay', null)
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

    public function getConfig(): ConnectionConfig
    {
        return $this->config;
    }

    private function establishConnection(): BunnyClient
    {
        if ($this->config->isSslOn()) {
            throw new \LogicException('The bunny library does not support SSL connections');
        }

        if (false == $this->client) {
            $bunnyConfig = [];
            $bunnyConfig['host'] = $this->config->getHost();
            $bunnyConfig['port'] = $this->config->getPort();
            $bunnyConfig['vhost'] = $this->config->getVHost();
            $bunnyConfig['user'] = $this->config->getUser();
            $bunnyConfig['password'] = $this->config->getPass();
            $bunnyConfig['read_write_timeout'] = min($this->config->getReadTimeout(), $this->config->getWriteTimeout());
            $bunnyConfig['timeout'] = $this->config->getConnectionTimeout();

            // @see https://github.com/php-enqueue/enqueue-dev/issues/229
//            $bunnyConfig['persistent'] = $this->config->isPersisted();
//            if ($this->config->isPersisted()) {
//                $bunnyConfig['path'] = 'enqueue';//$this->config->getOption('path', $this->config->getOption('vhost'));
//            }

            if ($this->config->getHeartbeat()) {
                $bunnyConfig['heartbeat'] = $this->config->getHeartbeat();
            }

            if (null !== $this->config->getOption('tcp_nodelay')) {
                $bunnyConfig['tcp_nodelay'] = $this->config->getOption('tcp_nodelay');
            }

            $this->client = new BunnyClient($bunnyConfig);
            $this->client->connect();
        }

        return $this->client;
    }
}
