<?php

namespace Enqueue\Client\ConsumptionExtension;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Route;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\MessageReceivedExtensionInterface;

final class ExclusiveCommandExtension implements MessageReceivedExtensionInterface
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var Route[]
     */
    private $queueToRouteMap;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function onMessageReceived(MessageReceived $context): void
    {
        $message = $context->getMessage();
        if ($message->getProperty(Config::TOPIC)) {
            return;
        }
        if ($message->getProperty(Config::COMMAND)) {
            return;
        }
        if ($message->getProperty(Config::PROCESSOR)) {
            return;
        }

        if (null === $this->queueToRouteMap) {
            $this->queueToRouteMap = $this->buildMap();
        }

        $queue = $context->getConsumer()->getQueue();
        if (array_key_exists($queue->getQueueName(), $this->queueToRouteMap)) {
            $context->getLogger()->debug('[ExclusiveCommandExtension] This is a exclusive command queue and client\'s properties are not set. Setting them');

            $route = $this->queueToRouteMap[$queue->getQueueName()];
            $message->setProperty(Config::PROCESSOR, $route->getProcessor());
            $message->setProperty(Config::COMMAND, $route->getSource());
        }
    }

    private function buildMap(): array
    {
        $map = [];
        foreach ($this->driver->getRouteCollection()->all() as $route) {
            if (false == $route->isCommand()) {
                continue;
            }

            if (false == $route->isProcessorExclusive()) {
                continue;
            }

            $queueName = $this->driver->createRouteQueue($route)->getQueueName();
            if (array_key_exists($queueName, $map)) {
                throw new \LogicException('The queue name has been already bound by another exclusive command processor');
            }

            $map[$queueName] = $route;
        }

        return $map;
    }
}
