<?php

namespace Enqueue\Client\ConsumptionExtension;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Consumption\Context\MessageReceived;
use Enqueue\Consumption\MessageReceivedExtensionInterface;

class SetRouterPropertiesExtension implements MessageReceivedExtensionInterface
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @param DriverInterface $driver
     */
    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function onMessageReceived(MessageReceived $context): void
    {
        $message = $context->getMessage();
        if (false == $message->getProperty(Config::TOPIC)) {
            return;
        }
        if ($message->getProperty(Config::PROCESSOR)) {
            return;
        }

        $config = $this->driver->getConfig();
        $queue = $this->driver->createQueue($config->getRouterQueue());
        if ($context->getConsumer()->getQueue()->getQueueName() != $queue->getQueueName()) {
            return;
        }

        // RouterProcessor is our default message processor when that header is not set
        $message->setProperty(Config::PROCESSOR, $config->getRouterProcessor());

        $context->getLogger()->debug(
            '[SetRouterPropertiesExtension] '.
            sprintf('Set router processor "%s"', $config->getRouterProcessor())
        );
    }
}
