<?php

namespace Enqueue\Client;

use Enqueue\Consumption\Result;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProcessor;

class RouterProcessor implements PsrProcessor
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var array
     */
    private $eventRoutes;

    /**
     * @var array
     */
    private $commandRoutes;

    /**
     * @param DriverInterface $driver
     * @param array           $eventRoutes
     * @param array           $commandRoutes
     */
    public function __construct(DriverInterface $driver, array $eventRoutes = [], array $commandRoutes = [])
    {
        $this->driver = $driver;

        $this->eventRoutes = $eventRoutes;
        $this->commandRoutes = $commandRoutes;
    }

    /**
     * @param string $topicName
     * @param string $queueName
     * @param string $processorName
     */
    public function add($topicName, $queueName, $processorName)
    {
        if (Config::COMMAND_TOPIC === $topicName) {
            $this->commandRoutes[$processorName] = $queueName;
        } else {
            $this->eventRoutes[$topicName][] = [$processorName, $queueName];
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

        if (array_key_exists($topicName, $this->eventRoutes)) {
            foreach ($this->eventRoutes[$topicName] as $route) {
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
        $commandName = $message->getProperty(Config::PARAMETER_COMMAND_NAME);
        if (false == $commandName) {
            return Result::reject(sprintf(
                'Got message without required parameter: "%s"',
                Config::PARAMETER_COMMAND_NAME
            ));
        }

        if (isset($this->commandRoutes[$commandName])) {
            $processorMessage = clone $message;
            $processorMessage->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, $this->commandRoutes[$commandName]);
            $processorMessage->setProperty(Config::PARAMETER_PROCESSOR_NAME, $commandName);

            $this->driver->sendToProcessor($this->driver->createClientMessage($processorMessage));
        }

        return self::ACK;
    }
}
