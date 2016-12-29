<?php
namespace Enqueue\Client;

use Enqueue\Psr\Context;
use Enqueue\Psr\Message as PsrMessage;
use Enqueue\Consumption\MessageProcessorInterface;

class DelegateMessageProcessor implements MessageProcessorInterface
{
    /**
     * @var MessageProcessorRegistryInterface
     */
    private $registry;

    /**
     * @param MessageProcessorRegistryInterface $registry
     */
    public function __construct(MessageProcessorRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(PsrMessage $message, Context $context)
    {
        $processorName = $message->getProperty(Config::PARAMETER_PROCESSOR_NAME);
        if (false == $processorName) {
            throw new \LogicException(sprintf(
                'Got message without required parameter: "%s"',
                Config::PARAMETER_PROCESSOR_NAME
            ));
        }

        return $this->registry->get($processorName)->process($message, $context);
    }
}
