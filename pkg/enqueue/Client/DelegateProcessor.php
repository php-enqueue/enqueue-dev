<?php

namespace Enqueue\Client;

use Enqueue\ProcessorRegistryInterface;
use Interop\Queue\Context;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Processor;

class DelegateProcessor implements Processor
{
    /**
     * @var ProcessorRegistryInterface
     */
    private $registry;

    /**
     * @param ProcessorRegistryInterface $registry
     */
    public function __construct(ProcessorRegistryInterface $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function process(InteropMessage $message, Context $context)
    {
        $processorName = $message->getProperty(Config::PROCESSOR);
        if (false == $processorName) {
            throw new \LogicException(sprintf(
                'Got message without required parameter: "%s"',
                Config::PROCESSOR
            ));
        }

        return $this->registry->get($processorName)->process($message, $context);
    }
}
