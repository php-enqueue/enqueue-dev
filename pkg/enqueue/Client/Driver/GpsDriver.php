<?php

namespace Enqueue\Client\Driver;

use Enqueue\Gps\GpsContext;
use Enqueue\Gps\GpsQueue;
use Enqueue\Gps\GpsTopic;
use Interop\Queue\Destination;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @method GpsContext getContext
 * @method GpsQueue createQueue(string $name)
 */
class GpsDriver extends GenericDriver
{
    public function __construct(GpsContext $context, ...$args)
    {
        parent::__construct($context, ...$args);
    }

    public function setupBroker(LoggerInterface $logger = null): void
    {
        $logger = $logger ?: new NullLogger();
        $log = function ($text, ...$args) use ($logger) {
            $logger->debug(sprintf('[GpsDriver] '.$text, ...$args));
        };

        // setup router
        $routerTopic = $this->createRouterTopic();
        $routerQueue = $this->createQueue($this->getConfig()->getRouterQueue());

        $log('Subscribe router topic to queue: %s -> %s', $routerTopic->getTopicName(), $routerQueue->getQueueName());
        $this->getContext()->subscribe($routerTopic, $routerQueue);

        // setup queues
        $declaredQueues = [];
        foreach ($this->getRouteCollection()->all() as $route) {
            /** @var GpsQueue $queue */
            $queue = $this->createRouteQueue($route);
            if (array_key_exists($queue->getQueueName(), $declaredQueues)) {
                continue;
            }

            $topic = $this->getContext()->createTopic($queue->getQueueName());

            $log('Subscribe processor topic to queue: %s -> %s', $topic->getTopicName(), $queue->getQueueName());
            $this->getContext()->subscribe($topic, $queue);

            $declaredQueues[$queue->getQueueName()] = true;
        }
    }

    /**
     * @return GpsTopic
     */
    protected function createRouterTopic(): Destination
    {
        return $this->doCreateTopic(
            $this->createTransportRouterTopicName($this->getConfig()->getRouterTopic(), true)
        );
    }
}
