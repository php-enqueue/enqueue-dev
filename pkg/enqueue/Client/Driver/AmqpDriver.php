<?php

declare(strict_types=1);

namespace  Enqueue\Client\Driver;

use Enqueue\Client\Config;
use Enqueue\Client\Message;
use Enqueue\Client\MessagePriority;
use Enqueue\Client\RouteCollection;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrTopic;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class AmqpDriver extends GenericDriver
{
    /**
     * @var AmqpContext
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
     * @var RouteCollection
     */
    private $routeCollection;

    public function __construct(AmqpContext $context, Config $config, RouteCollection $routeCollection)
    {
        $this->context = $context;
        $this->config = $config;
        $this->routeCollection = $routeCollection;

        $this->priorityMap = [
            MessagePriority::VERY_LOW => 0,
            MessagePriority::LOW => 1,
            MessagePriority::NORMAL => 2,
            MessagePriority::HIGH => 3,
            MessagePriority::VERY_HIGH => 4,
        ];

        parent::__construct($context, $config, $routeCollection);
    }

    /**
     * @return AmqpMessage
     */
    public function createTransportMessage(Message $clientMessage): PsrMessage
    {
        /** @var AmqpMessage $transportMessage */
        $transportMessage = parent::createTransportMessage($clientMessage);
        $transportMessage->setDeliveryMode(AmqpMessage::DELIVERY_MODE_PERSISTENT);
        $transportMessage->setContentType($clientMessage->getContentType());

        if ($clientMessage->getExpire()) {
            $transportMessage->setExpiration($clientMessage->getExpire() * 1000);
        }

        if ($priority = $clientMessage->getPriority()) {
            if (false == array_key_exists($priority, $this->getPriorityMap())) {
                throw new \InvalidArgumentException(sprintf(
                    'Cant convert client priority "%s" to transport one. Could be one of "%s"',
                    $priority,
                    implode('", "', array_keys($this->getPriorityMap()))
                ));
            }

            $transportMessage->setPriority($this->priorityMap[$priority]);
        }

        return $transportMessage;
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
        $logger = $logger ?: new NullLogger();
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[AmqpDriver] '.$text, ...$args));
        };

        // setup router
        $routerTopic = $this->createRouterTopic();
        $log('Declare router exchange: %s', $routerTopic->getTopicName());
        $this->context->declareTopic($routerTopic);

        $routerQueue = $this->createQueue($this->config->getRouterQueueName());
        $log('Declare router queue: %s', $routerQueue->getQueueName());
        $this->context->declareQueue($routerQueue);

        $log('Bind router queue to exchange: %s -> %s', $routerQueue->getQueueName(), $routerTopic->getTopicName());
        $this->context->bind(new AmqpBind($routerTopic, $routerQueue, $routerQueue->getQueueName()));

        // setup queues
        $declaredQueues = [];
        foreach ($this->routeCollection->all() as $route) {
            /** @var AmqpQueue $queue */
            $queue = $this->createRouteQueue($route);
            if (array_key_exists($queue->getQueueName(), $declaredQueues)) {
                continue;
            }

            $log('Declare processor queue: %s', $queue->getQueueName());
            $this->context->declareQueue($queue);

            $declaredQueues[$queue->getQueueName()] = true;
        }
    }

    /**
     * @return AmqpQueue
     */
    public function createQueue(string $clientQueuName): PsrQueue
    {
        /** @var AmqpQueue $queue */
        $queue = parent::createQueue($clientQueuName);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);

        return $queue;
    }

    /**
     * @param AmqpTopic   $topic
     * @param AmqpMessage $transportMessage
     */
    protected function doSendToRouter(PsrTopic $topic, PsrMessage $transportMessage): void
    {
        // We should not handle priority, expiration, and delay at this stage.
        // The router will take care of it while re-sending the message to the final destinations.
        $transportMessage->setPriority(null);
        $transportMessage->setExpiration(null);

        $this->context->createProducer()->send($topic, $transportMessage);
    }

    /**
     * @return AmqpTopic
     */
    protected function createRouterTopic(): PsrTopic
    {
        /** @var AmqpTopic $topic */
        $topic = parent::createRouterTopic();
        $topic->setType(AmqpTopic::TYPE_FANOUT);
        $topic->addFlag(AmqpTopic::FLAG_DURABLE);

        return $topic;
    }

    protected function getPriorityMap(): array
    {
        return $this->priorityMap;
    }
}
