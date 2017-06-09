<?php

namespace Enqueue\Client;

use Enqueue\Consumption\Result;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;

class RouterProcessor implements PsrProcessor
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var array
     */
    private $routes;

    /**
     * @var array
     */
    private $commands;

    /**
     * @param DriverInterface $driver
     * @param array           $routes
     */
    public function __construct(DriverInterface $driver, array $routes = [])
    {
        $this->driver = $driver;
        $this->routes = $routes;
    }

    /**
     * @param string $topicName
     * @param string $queueName
     * @param string $processorName
     */
    public function add($topicName, $queueName, $processorName)
    {
        if (Config::COMMAND_TOPIC === $topicName) {
            $this->commands[$processorName] = $queueName;
        } else {
            $this->routes[$topicName][] = [$processorName, $queueName];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $message, PsrContext $context)
    {
        $topicName = $message->getProperty(Config::PARAMETER_TOPIC_NAME);
        if (false == $topicName) {
            return Result::reject(sprintf(
                'Got message without required parameter: "%s"',
                Config::PARAMETER_TOPIC_NAME
            ));
        }

        if (Config::COMMAND_TOPIC === $topicName) {
            return $this->routeCommand($message);
        }

        return $this->routeEvent($message);
    }

    /**
     * @param PsrMessage $message
     *
     * @return string|Result
     */
    private function routeEvent(PsrMessage $message)
    {
        $topicName = $message->getProperty(Config::PARAMETER_TOPIC_NAME);

        if (array_key_exists($topicName, $this->routes)) {
            foreach ($this->routes[$topicName] as $route) {
                $processorMessage = clone $message;
                $processorMessage->setProperty(Config::PARAMETER_PROCESSOR_NAME, $route[0]);
                $processorMessage->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, $route[1]);

                $this->driver->sendToProcessor($this->driver->createClientMessage($processorMessage));
            }
        }

        return self::ACK;
    }

    /**
     * @param PsrMessage $message
     *
     * @return string|Result
     */
    private function routeCommand(PsrMessage $message)
    {
        $processorName = $message->getProperty(Config::PARAMETER_PROCESSOR_NAME);
        if (false == $processorName) {
            return Result::reject(sprintf(
                'Got message without required parameter: "%s"',
                Config::PARAMETER_PROCESSOR_NAME
            ));
        }

        if (isset($this->commands[$processorName])) {
            $processorMessage = clone $message;
            $processorMessage->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, $this->commands[$processorName]);

            $this->driver->sendToProcessor($this->driver->createClientMessage($processorMessage));
        }

        return self::ACK;
    }
}
