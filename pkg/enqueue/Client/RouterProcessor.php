<?php

namespace Enqueue\Client;

use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

final class RouterProcessor implements PsrProcessor
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var RouteCollection
     */
    private $routeCollection;

    public function __construct(DriverInterface $driver, RouteCollection $routeCollection)
    {
        $this->driver = $driver;
        $this->routeCollection = $routeCollection;
    }

    public function process(PsrMessage $message, PsrContext $context): Result
    {
        $topic = $message->getProperty(Config::PARAMETER_TOPIC_NAME);
        if ($topic) {
            return $this->routeEvent($topic, $message);
        }

        $command = $message->getProperty(Config::PARAMETER_COMMAND_NAME);
        if ($command) {
            return $this->routeCommand($command, $message);
        }

        return Result::reject(sprintf(
            'Got message without required parameters. Either "%s" or "%s" property should be set',
            Config::PARAMETER_TOPIC_NAME,
            Config::PARAMETER_COMMAND_NAME
        ));
    }

    private function routeEvent(string $topic, PsrMessage $message): Result
    {
        $count = 0;
        foreach ($this->routeCollection->topicRoutes($topic) as $route) {
            $processorMessage = clone $message;
            $processorMessage->setProperty(Config::PARAMETER_PROCESSOR_NAME, $route->getProcessor());
            $processorMessage->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, $route->getQueue());

            $this->driver->sendToProcessor($this->driver->createClientMessage($processorMessage));

            ++$count;
        }

        return Result::ack(sprintf('Routed to "%d" event subscribers', $count));
    }

    private function routeCommand(string $command, PsrMessage $message): Result
    {
        $route = $this->routeCollection->commandRoute($command);
        if (false == $route) {
            throw new \LogicException(sprintf('The command "%s" processor not found', $command));
        }

        $processorMessage = clone $message;
        $processorMessage->setProperty(Config::PARAMETER_PROCESSOR_NAME, $route->getProcessor());
        $processorMessage->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, $route->getQueue());

        $this->driver->sendToProcessor($this->driver->createClientMessage($processorMessage));

        return Result::ack('Routed to the command processor');
    }
}
