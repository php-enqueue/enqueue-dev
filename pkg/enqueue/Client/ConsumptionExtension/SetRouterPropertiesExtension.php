<?php

namespace Enqueue\Client\ConsumptionExtension;

use Enqueue\Client\Config;
use Enqueue\Client\DriverInterface;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;

class SetRouterPropertiesExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

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

    /**
     * {@inheritdoc}
     */
    public function onPreReceived(Context $context)
    {
        $message = $context->getPsrMessage();
        if ($message->getProperty(Config::PARAMETER_PROCESSOR_NAME)) {
            return;
        }

        $config = $this->driver->getConfig();
        $queue = $this->driver->createQueue($config->getRouterQueueName());
        if ($context->getPsrQueue()->getQueueName() != $queue->getQueueName()) {
            return;
        }

        // RouterProcessor is our default message processor when that header is not set
        $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, $config->getRouterProcessorName());
        $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, $config->getRouterQueueName());
    }
}
