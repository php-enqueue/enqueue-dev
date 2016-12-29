<?php
namespace Enqueue\Client;

use Enqueue\Psr\Context as PsrContext;
use Enqueue\Psr\Message as PsrMessage;
use Enqueue\Consumption\MessageProcessorInterface;
use Enqueue\Consumption\Result;

class RouterProcessor implements MessageProcessorInterface
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
        $this->routes[$topicName][] = [$processorName, $queueName];
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $message, PsrContext $context)
    {
        $topicName = $message->getProperty(Config::PARAMETER_TOPIC_NAME);
        if (false == $topicName) {
            throw new \LogicException(sprintf(
                'Got message without required parameter: "%s"',
                Config::PARAMETER_TOPIC_NAME
            ));
        }

        if (array_key_exists($topicName, $this->routes)) {
            foreach ($this->routes[$topicName] as $route) {
                $processorMessage = clone $message;
                $processorMessage->setProperty(Config::PARAMETER_PROCESSOR_NAME, $route[0]);
                $processorMessage->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, $route[1]);

                $this->driver->sendToProcessor($this->driver->createClientMessage($processorMessage));
            }
        }

        return Result::ACK;
    }
}
