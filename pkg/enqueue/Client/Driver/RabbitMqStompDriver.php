<?php

namespace Enqueue\Client\Driver;

use Enqueue\Client\Config;
use Enqueue\Client\Message;
use Enqueue\Client\RouteCollection;
use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\StompDestination;
use Enqueue\Stomp\StompMessage;
use Enqueue\Stomp\StompProducer;
use Interop\Queue\Destination;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Producer as InteropProducer;
use Interop\Queue\Queue as InteropQueue;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class RabbitMqStompDriver extends StompDriver
{
    /**
     * @var StompManagementClient
     */
    private $management;

    public function __construct(StompContext $context, Config $config, RouteCollection $routeCollection, StompManagementClient $management)
    {
        parent::__construct($context, $config, $routeCollection);

        $this->management = $management;
    }

    /**
     * @return StompMessage
     */
    public function createTransportMessage(Message $message): InteropMessage
    {
        $transportMessage = parent::createTransportMessage($message);

        if ($message->getExpire()) {
            $transportMessage->setHeader('expiration', (string) ($message->getExpire() * 1000));
        }

        if ($priority = $message->getPriority()) {
            $priorityMap = $this->getPriorityMap();

            if (false == array_key_exists($priority, $priorityMap)) {
                throw new \LogicException(sprintf('Cant convert client priority to transport: "%s"', $priority));
            }

            $transportMessage->setHeader('priority', $priorityMap[$priority]);
        }

        if ($message->getDelay()) {
            if (false == $this->getConfig()->getTransportOption('delay_plugin_installed', false)) {
                throw new \LogicException('The message delaying is not supported. In order to use delay feature install RabbitMQ delay plugin.');
            }

            $transportMessage->setHeader('x-delay', (string) ($message->getDelay() * 1000));
        }

        return $transportMessage;
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
        $logger = $logger ?: new NullLogger();
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[RabbitMqStompDriver] '.$text, ...$args));
        };

        if (false == $this->getConfig()->getTransportOption('management_plugin_installed', false)) {
            $log('Could not setup broker. The option `management_plugin_installed` is not enabled. Please enable that option and install rabbit management plugin');

            return;
        }

        // setup router
        $routerExchange = $this->createTransportRouterTopicName($this->getConfig()->getRouterTopic(), true);
        $log('Declare router exchange: %s', $routerExchange);
        $this->management->declareExchange($routerExchange, [
            'type' => 'fanout',
            'durable' => true,
            'auto_delete' => false,
        ]);

        $routerQueue = $this->createTransportQueueName($this->getConfig()->getRouterQueue(), true);
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
        foreach ($this->getRouteCollection()->all() as $route) {
            $queue = $this->createRouteQueue($route);

            $log('Declare processor queue: %s', $queue->getStompName());
            $this->management->declareQueue($queue->getStompName(), [
                'auto_delete' => false,
                'durable' => true,
                'arguments' => [
                    'x-max-priority' => 4,
                ],
            ]);
        }

        // setup delay exchanges
        if ($this->getConfig()->getTransportOption('delay_plugin_installed', false)) {
            foreach ($this->getRouteCollection()->all() as $route) {
                $queue = $this->createRouteQueue($route);
                $delayExchange = $queue->getStompName().'.delayed';

                $log('Declare delay exchange: %s', $delayExchange);
                $this->management->declareExchange($delayExchange, [
                    'type' => 'x-delayed-message',
                    'durable' => true,
                    'auto_delete' => false,
                    'arguments' => [
                        'x-delayed-type' => 'direct',
                    ],
                ]);

                $log('Bind processor queue to delay exchange: %s -> %s', $queue->getStompName(), $delayExchange);
                $this->management->bind($delayExchange, $queue->getStompName(), $queue->getStompName());
            }
        } else {
            $log('Delay exchange and bindings are not setup. if you\'d like to use delays please install delay rabbitmq plugin and set delay_plugin_installed option to true');
        }
    }

    /**
     * @return StompDestination
     */
    protected function doCreateQueue(string $transportQueueName): InteropQueue
    {
        $queue = parent::doCreateQueue($transportQueueName);
        $queue->setHeader('x-max-priority', 4);

        return $queue;
    }

    /**
     * @param StompProducer    $producer
     * @param StompDestination $topic
     * @param StompMessage     $transportMessage
     */
    protected function doSendToRouter(InteropProducer $producer, Destination $topic, InteropMessage $transportMessage): void
    {
        // We should not handle priority, expiration, and delay at this stage.
        // The router will take care of it while re-sending the message to the final destinations.
        $transportMessage->setHeader('expiration', null);
        $transportMessage->setHeader('priority', null);
        $transportMessage->setHeader('x-delay', null);

        $producer->send($topic, $transportMessage);
    }

    /**
     * @param StompProducer    $producer
     * @param StompDestination $destination
     * @param StompMessage     $transportMessage
     */
    protected function doSendToProcessor(InteropProducer $producer, InteropQueue $destination, InteropMessage $transportMessage): void
    {
        if ($delay = $transportMessage->getProperty(Config::DELAY)) {
            $producer->setDeliveryDelay(null);
            $destination = $this->createDelayedTopic($destination);
        }

        $producer->send($destination, $transportMessage);
    }

    private function createDelayedTopic(StompDestination $queue): StompDestination
    {
        // in order to use delay feature make sure the rabbitmq_delayed_message_exchange plugin is installed.
        $destination = $this->getContext()->createTopic($queue->getStompName().'.delayed');
        $destination->setType(StompDestination::TYPE_EXCHANGE);
        $destination->setDurable(true);
        $destination->setAutoDelete(false);
        $destination->setRoutingKey($queue->getStompName());

        return $destination;
    }
}
