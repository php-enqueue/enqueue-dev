<?php

namespace Enqueue\Client\Driver;

use Enqueue\Sqs\SqsContext;
use Enqueue\Sqs\SqsDestination;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @method SqsContext getContext
 * @method SqsDestination createQueue(string $clientQueueName): InteropQueue
 */
class SqsDriver extends GenericDriver
{
    public function __construct(SqsContext $context, ...$args)
    {
        parent::__construct($context, ...$args);
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
        $logger = $logger ?: new NullLogger();
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[SqsDriver] '.$text, ...$args));
        };

        // setup router
        $routerQueue = $this->createQueue($this->getConfig()->getRouterQueue());
        $log('Declare router queue: %s', $routerQueue->getQueueName());
        $this->getContext()->declareQueue($routerQueue);

        // setup queues
        $declaredQueues = [];
        foreach ($this->getRouteCollection()->all() as $route) {
            /** @var SqsDestination $queue */
            $queue = $this->createRouteQueue($route);
            if (array_key_exists($queue->getQueueName(), $declaredQueues)) {
                continue;
            }

            $log('Declare processor queue: %s', $queue->getQueueName());
            $this->getContext()->declareQueue($queue);

            $declaredQueues[$queue->getQueueName()] = true;
        }
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
