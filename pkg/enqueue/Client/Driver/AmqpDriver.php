<?php

declare(strict_types=1);

namespace  Enqueue\Client\Driver;

use Enqueue\AmqpExt\AmqpProducer;
use Enqueue\Client\Message;
use Interop\Amqp\AmqpContext;
use Interop\Amqp\AmqpMessage;
use Interop\Amqp\AmqpQueue;
use Interop\Amqp\AmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Queue\Destination;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Producer as InteropProducer;
use Interop\Queue\Queue as InteropQueue;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @method AmqpContext getContext
 */
class AmqpDriver extends GenericDriver
{
    public function __construct(AmqpContext $context, ...$args)
    {
        parent::__construct($context, ...$args);
    }

    /**
     * @return AmqpMessage
     */
    public function createTransportMessage(Message $clientMessage): InteropMessage
    {
        /** @var AmqpMessage $transportMessage */
        $transportMessage = parent::createTransportMessage($clientMessage);
        $transportMessage->setDeliveryMode(AmqpMessage::DELIVERY_MODE_PERSISTENT);
        $transportMessage->setContentType($clientMessage->getContentType());

        if ($clientMessage->getExpire()) {
            $transportMessage->setExpiration($clientMessage->getExpire() * 1000);
        }

        $priorityMap = $this->getPriorityMap();
        if ($priority = $clientMessage->getPriority()) {
            if (false == array_key_exists($priority, $priorityMap)) {
                throw new \InvalidArgumentException(sprintf(
                    'Cant convert client priority "%s" to transport one. Could be one of "%s"',
                    $priority,
                    implode('", "', array_keys($priorityMap))
                ));
            }

            $transportMessage->setPriority($priorityMap[$priority]);
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
        $this->getContext()->declareTopic($routerTopic);

        $routerQueue = $this->createQueue($this->getConfig()->getRouterQueue());
        $log('Declare router queue: %s', $routerQueue->getQueueName());
        $this->getContext()->declareQueue($routerQueue);

        $log('Bind router queue to exchange: %s -> %s', $routerQueue->getQueueName(), $routerTopic->getTopicName());
        $this->getContext()->bind(new AmqpBind($routerTopic, $routerQueue, $routerQueue->getQueueName()));

        // setup queues
        $declaredQueues = [];
        foreach ($this->getRouteCollection()->all() as $route) {
            /** @var AmqpQueue $queue */
            $queue = $this->createRouteQueue($route);
            if (array_key_exists($queue->getQueueName(), $declaredQueues)) {
                continue;
            }

            $log('Declare processor queue: %s', $queue->getQueueName());
            $this->getContext()->declareQueue($queue);

            $declaredQueues[$queue->getQueueName()] = true;
        }
    }

    /**
     * @return AmqpTopic
     */
    protected function createRouterTopic(): Destination
    {
        $topic = $this->doCreateTopic(
            $this->createTransportRouterTopicName($this->getConfig()->getRouterTopic(), true)
        );
        $topic->setType(AmqpTopic::TYPE_FANOUT);
        $topic->addFlag(AmqpTopic::FLAG_DURABLE);

        return $topic;
    }

    /**
     * @return AmqpQueue
     */
    protected function doCreateQueue(string $transportQueueName): InteropQueue
    {
        /** @var AmqpQueue $queue */
        $queue = parent::doCreateQueue($transportQueueName);
        $queue->addFlag(AmqpQueue::FLAG_DURABLE);

        return $queue;
    }

    /**
     * @param AmqpProducer $producer
     * @param AmqpTopic    $topic
     * @param AmqpMessage  $transportMessage
     */
    protected function doSendToRouter(InteropProducer $producer, Destination $topic, InteropMessage $transportMessage): void
    {
        // We should not handle priority, expiration, and delay at this stage.
        // The router will take care of it while re-sending the message to the final destinations.
        $transportMessage->setPriority(null);
        $transportMessage->setExpiration(null);

        $producer->send($topic, $transportMessage);
    }
}
