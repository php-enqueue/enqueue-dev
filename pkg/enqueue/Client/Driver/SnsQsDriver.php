<?php

namespace Enqueue\Client\Driver;

use Enqueue\SnsQs\SnsQsContext;
use Enqueue\SnsQs\SnsQsTopic;
use Interop\Queue\Destination;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @method SnsQsContext getContext()
 * @method SnsQsTopic   createRouterTopic()
 */
class SnsQsDriver extends GenericDriver
{
    public function __construct(SnsQsContext $context, ...$args)
    {
        parent::__construct($context, ...$args);
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
        $logger = $logger ?: new NullLogger();
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[SqsQsDriver] '.$text, ...$args));
        };

        // setup router
        $routerTopic = $this->createRouterTopic();
        $log('Declare router topic: %s', $routerTopic->getTopicName());
        $this->getContext()->declareTopic($routerTopic);

        $routerQueue = $this->createQueue($this->getConfig()->getRouterQueue());
        $log('Declare router queue: %s', $routerQueue->getQueueName());
        $this->getContext()->declareQueue($routerQueue);

        $log('Bind router queue to topic: %s -> %s', $routerQueue->getQueueName(), $routerTopic->getTopicName());
        $this->getContext()->bind($routerTopic, $routerQueue);

        // setup queues
        $declaredQueues = [];
        $declaredTopics = [];
        foreach ($this->getRouteCollection()->all() as $route) {
            $queue = $this->createRouteQueue($route);
            if (false === array_key_exists($queue->getQueueName(), $declaredQueues)) {
                $log('Declare processor queue: %s', $queue->getQueueName());
                $this->getContext()->declareQueue($queue);

                $declaredQueues[$queue->getQueueName()] = true;
            }

            if ($route->isCommand()) {
                continue;
            }

            $topic = $this->doCreateTopic($this->createTransportQueueName($route->getSource(), true));
            if (false === array_key_exists($topic->getTopicName(), $declaredTopics)) {
                $log('Declare processor topic: %s', $topic->getTopicName());
                $this->getContext()->declareTopic($topic);

                $declaredTopics[$topic->getTopicName()] = true;
            }

            $log('Bind processor queue to topic: %s -> %s', $queue->getQueueName(), $topic->getTopicName());
            $this->getContext()->bind($topic, $queue);
        }
    }

    protected function createRouterTopic(): Destination
    {
        return $this->doCreateTopic(
            $this->createTransportRouterTopicName($this->getConfig()->getRouterTopic(), true)
        );
    }

    protected function createTransportRouterTopicName(string $name, bool $prefix): string
    {
        $name = parent::createTransportRouterTopicName($name, $prefix);

        return str_replace('.', '_dot_', $name);
    }

    protected function createTransportQueueName(string $name, bool $prefix): string
    {
        $name = parent::createTransportQueueName($name, $prefix);

        return str_replace('.', '_dot_', $name);
    }
}
