<?php

namespace Enqueue\Client\ConsumptionExtension;

use Enqueue\Client\Config;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface;

class ExclusiveCommandExtension implements ExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * @var array
     */
    private $queueNameToProcessorNameMap;

    /**
     * @param array $queueNameToProcessorNameMap
     */
    public function __construct(array $queueNameToProcessorNameMap)
    {
        $this->queueNameToProcessorNameMap = $queueNameToProcessorNameMap;
    }

    public function onPreReceived(Context $context)
    {
        $message = $context->getPsrMessage();
        $queue = $context->getPsrQueue();

        if ($message->getProperty(Config::PARAMETER_TOPIC_NAME)) {
            return;
        }
        if ($message->getProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME)) {
            return;
        }
        if ($message->getProperty(Config::PARAMETER_PROCESSOR_NAME)) {
            return;
        }

        if (array_key_exists($queue->getQueueName(), $this->queueNameToProcessorNameMap)) {
            $context->getLogger()->debug('[ExclusiveCommandExtension] This is a exclusive command queue and client\'s properties are not set. Setting them');

            $message->setProperty(Config::PARAMETER_TOPIC_NAME, Config::COMMAND_TOPIC);
            $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, $queue->getQueueName());
            $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, $this->queueNameToProcessorNameMap[$queue->getQueueName()]);
        }
    }
}
