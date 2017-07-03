<?php

namespace Enqueue\Client\ConsumptionExtension;

use Enqueue\Client\Config;
use Enqueue\Client\ExtensionInterface as ClientExtensionInterface;
use Enqueue\Client\Message;
use Enqueue\Consumption\Context;
use Enqueue\Consumption\EmptyExtensionTrait;
use Enqueue\Consumption\ExtensionInterface as ConsumptionExtensionInterface;

class ExclusiveCommandExtension implements ConsumptionExtensionInterface, ClientExtensionInterface
{
    use EmptyExtensionTrait;

    /**
     * @var string[]
     */
    private $queueNameToProcessorNameMap;

    /**
     * @var string[]
     */
    private $processorNameToQueueNameMap;

    /**
     * @param string[] $queueNameToProcessorNameMap
     */
    public function __construct(array $queueNameToProcessorNameMap)
    {
        $this->queueNameToProcessorNameMap = $queueNameToProcessorNameMap;
        $this->processorNameToQueueNameMap = array_flip($queueNameToProcessorNameMap);
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
        if ($message->getProperty(Config::PARAMETER_COMMAND_NAME)) {
            return;
        }

        if (array_key_exists($queue->getQueueName(), $this->queueNameToProcessorNameMap)) {
            $context->getLogger()->debug('[ExclusiveCommandExtension] This is a exclusive command queue and client\'s properties are not set. Setting them');

            $message->setProperty(Config::PARAMETER_TOPIC_NAME, Config::COMMAND_TOPIC);
            $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, $queue->getQueueName());
            $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, $this->queueNameToProcessorNameMap[$queue->getQueueName()]);
            $message->setProperty(Config::PARAMETER_COMMAND_NAME, $this->queueNameToProcessorNameMap[$queue->getQueueName()]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPreSend($topic, Message $message)
    {
        if (Config::COMMAND_TOPIC != $topic) {
            return;
        }

        $commandName = $message->getProperty(Config::PARAMETER_COMMAND_NAME);
        if (array_key_exists($commandName, $this->processorNameToQueueNameMap)) {
            $message->setProperty(Config::PARAMETER_PROCESSOR_NAME, $commandName);
            $message->setProperty(Config::PARAMETER_PROCESSOR_QUEUE_NAME, $this->processorNameToQueueNameMap[$commandName]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function onPostSend($topic, Message $message)
    {
    }
}
