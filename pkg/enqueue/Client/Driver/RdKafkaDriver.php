<?php

declare(strict_types=1);

namespace Enqueue\Client\Driver;

use Enqueue\RdKafka\RdKafkaContext;
use Enqueue\RdKafka\RdKafkaTopic;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @method RdKafkaContext getContext()
 */
class RdKafkaDriver extends GenericDriver
{
    public function __construct(RdKafkaContext $context, ...$args)
    {
        parent::__construct($context, ...$args);
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
        $logger = $logger ?: new NullLogger();
        $logger->debug('[RdKafkaDriver] setup broker');
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[RdKafkaDriver] '.$text, ...$args));
        };

        // setup router
        $routerQueue = $this->createQueue($this->getConfig()->getRouterQueue());
        $log('Create router queue: %s', $routerQueue->getQueueName());
        $this->getContext()->createConsumer($routerQueue);

        // setup queues
        $declaredQueues = [];
        foreach ($this->getRouteCollection()->all() as $route) {
            /** @var RdKafkaTopic $queue */
            $queue = $this->createRouteQueue($route);
            if (array_key_exists($queue->getQueueName(), $declaredQueues)) {
                continue;
            }

            $log('Create processor queue: %s', $queue->getQueueName());
            $this->getContext()->createConsumer($queue);
        }
    }
}
