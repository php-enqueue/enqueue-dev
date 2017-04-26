<?php

namespace Enqueue\Stomp\Client;

use Enqueue\Client\Config;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Psr\PsrMessage;
use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\StompDestination;
use Enqueue\Stomp\StompMessage;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RabbitMqStompDriver extends StompDriver
{
    /**
     * @var StompContext
     */
    private $context;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var array
     */
    private $priorityMap;

    /**
     * @var ManagementClient
     */
    private $management;

    /**
     * @var QueueMetaRegistry
     */
    private $queueMetaRegistry;

    /**
     * @param StompContext      $context
     * @param Config            $config
     * @param QueueMetaRegistry $queueMetaRegistry
     * @param ManagementClient  $management
     */
    public function __construct(StompContext $context, Config $config, QueueMetaRegistry $queueMetaRegistry, ManagementClient $management)
    {
        parent::__construct($context, $config, $queueMetaRegistry);

        $this->context = $context;
        $this->config = $config;
        $this->queueMetaRegistry = $queueMetaRegistry;
        $this->management = $management;

        $this->priorityMap = [
            MessagePriority::VERY_LOW => 0,
            MessagePriority::LOW => 1,
            MessagePriority::NORMAL => 2,
            MessagePriority::HIGH => 3,
            MessagePriority::VERY_HIGH => 4,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return StompMessage
     */
    public function createTransportMessage(Message $message)
    {
        $transportMessage = parent::createTransportMessage($message);

        if ($message->getExpire()) {
            $transportMessage->setHeader('expiration', (string) ($message->getExpire() * 1000));
        }

        if ($priority = $message->getPriority()) {
            if (false == array_key_exists($priority, $this->priorityMap)) {
                throw new \LogicException(sprintf('Cant convert client priority to transport: "%s"', $priority));
            }

            $transportMessage->setHeader('priority', $this->priorityMap[$priority]);
        }

        if ($message->getDelay()) {
            if (false == $this->config->getTransportOption('delay_plugin_installed', false)) {
                throw new \LogicException('The message delaying is not supported. In order to use delay feature install RabbitMQ delay plugin.');
            }

            $transportMessage->setHeader('x-delay', (string) ($message->getDelay() * 1000));
        }

        return $transportMessage;
    }

    /**
     * @param StompMessage $message
     *
     * {@inheritdoc}
     */
    public function createClientMessage(PsrMessage $message)
    {
        $clientMessage = parent::createClientMessage($message);

        $headers = $clientMessage->getHeaders();
        unset(
            $headers['x-delay'],
            $headers['expiration'],
            $headers['priority']
        );
        $clientMessage->setHeaders($headers);

        if ($delay = $message->getHeader('x-delay')) {
            if (false == is_numeric($delay)) {
                throw new \LogicException(sprintf('x-delay header is not numeric. "%s"', $delay));
            }

            $clientMessage->setDelay((int) ((int) $delay) / 1000);
        }

        if ($expiration = $message->getHeader('expiration')) {
            if (false == is_numeric($expiration)) {
                throw new \LogicException(sprintf('expiration header is not numeric. "%s"', $expiration));
            }

            $clientMessage->setExpire((int) ((int) $expiration) / 1000);
        }

        if ($priority = $message->getHeader('priority')) {
            if (false === $clientPriority = array_search($priority, $this->priorityMap, true)) {
                throw new \LogicException(sprintf('Cant convert transport priority to client: "%s"', $priority));
            }

            $clientMessage->setPriority($clientPriority);
        }

        return $clientMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function sendToProcessor(Message $message)
    {
        if (false == $message->getProperty(Config::PARAMETER_PROCESSOR_NAME)) {
            throw new \LogicException('Processor name parameter is required but is not set');
        }

        if (false == $queueName = $message->getProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME)) {
            throw new \LogicException('Queue name parameter is required but is not set');
        }

        $transportMessage = $this->createTransportMessage($message);
        $destination = $this->createQueue($queueName);

        if ($message->getDelay()) {
            $destination = $this->createDelayedTopic($destination);
        }

        $this->context->createProducer()->send($destination, $transportMessage);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName)
    {
        $queue = parent::createQueue($queueName);
        $queue->setHeader('x-max-priority', 4);

        return $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function setupBroker(LoggerInterface $logger = null)
    {
        $logger = $logger ?: new NullLogger();
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[RabbitMqStompDriver] '.$text, ...$args));
        };

        if (false == $this->config->getTransportOption('management_plugin_installed', false)) {
            $log('Could not setup broker. The option `management_plugin_installed` is not enabled. Please enable that option and install rabbit management plugin');

            return;
        }

        // setup router
        $routerExchange = $this->config->createTransportRouterTopicName($this->config->getRouterTopicName());
        $log('Declare router exchange: %s', $routerExchange);
        $this->management->declareExchange($routerExchange, [
            'type' => 'fanout',
            'durable' => true,
            'auto_delete' => false,
        ]);

        $routerQueue = $this->config->createTransportQueueName($this->config->getRouterQueueName());
        $log('Declare router queue: %s', $routerQueue);
        $this->management->declareQueue($routerQueue, [
            'auto_delete' => false,
            'durable' => true,
            'arguments' => [
                'x-max-priority' => 4,
            ],
        ]);

        $log('Bind router queue to exchange: %s -> %s', $routerQueue, $routerExchange);
        $this->management->bind($routerExchange, $routerQueue, $routerQueue);

        // setup queues
        foreach ($this->queueMetaRegistry->getQueuesMeta() as $meta) {
            $queue = $this->config->createTransportQueueName($meta->getClientName());

            $log('Declare processor queue: %s', $queue);
            $this->management->declareQueue($queue, [
                'auto_delete' => false,
                'durable' => true,
                'arguments' => [
                    'x-max-priority' => 4,
                ],
            ]);
        }

        // setup delay exchanges
        if ($this->config->getTransportOption('delay_plugin_installed', false)) {
            foreach ($this->queueMetaRegistry->getQueuesMeta() as $meta) {
                $queue = $this->config->createTransportQueueName($meta->getClientName());
                $delayExchange = $queue.'.delayed';

                $log('Declare delay exchange: %s', $delayExchange);
                $this->management->declareExchange($delayExchange, [
                    'type' => 'x-delayed-message',
                    'durable' => true,
                    'auto_delete' => false,
                    'arguments' => [
                        'x-delayed-type' => 'direct',
                    ],
                ]);

                $log('Bind processor queue to delay exchange: %s -> %s', $queue, $delayExchange);
                $this->management->bind($delayExchange, $queue, $queue);
            }
        } else {
            $log('Delay exchange and bindings are not setup. if you\'d like to use delays please install delay rabbitmq plugin and set delay_plugin_installed option to true');
        }
    }

    /**
     * @param StompDestination $queue
     *
     * @return StompDestination
     */
    private function createDelayedTopic(StompDestination $queue)
    {
        // in order to use delay feature make sure the rabbitmq_delayed_message_exchange plugin is installed.
        $destination = $this->context->createTopic($queue->getStompName().'.delayed');
        $destination->setType(StompDestination::TYPE_EXCHANGE);
        $destination->setDurable(true);
        $destination->setAutoDelete(false);
        $destination->setRoutingKey($queue->getStompName());

        return $destination;
    }
}
