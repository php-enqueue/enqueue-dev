<?php

namespace  Enqueue\Client\Driver;

use Enqueue\Fs\FsContext;
use Enqueue\Fs\FsDestination;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @method FsContext getContext
 * @method FsDestination createQueue(string $name)
 */
class FsDriver extends GenericDriver
{
    public function __construct(FsContext $context, ...$args)
    {
        parent::__construct($context, ...$args);
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
        $logger = $logger ?: new NullLogger();
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[FsDriver] '.$text, ...$args));
        };

        // setup router
        $routerQueue = $this->createQueue($this->getConfig()->getRouterQueue());

        $log('Declare router queue "%s" file: %s', $routerQueue->getQueueName(), $routerQueue->getFileInfo());
        $this->getContext()->declareDestination($routerQueue);

        // setup queues
        $declaredQueues = [];
        foreach ($this->getRouteCollection()->all() as $route) {
            /** @var FsDestination $queue */
            $queue = $this->createRouteQueue($route);
            if (array_key_exists($queue->getQueueName(), $declaredQueues)) {
                continue;
            }

            $log('Declare processor queue "%s" file: %s', $queue->getQueueName(), $queue->getFileInfo());
            $this->getContext()->declareDestination($queue);

            $declaredQueues[$queue->getQueueName()] = true;
        }
    }
}
