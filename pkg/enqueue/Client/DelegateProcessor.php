<?php

namespace Enqueue\Client;

use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrMessage;
use Enqueue\Psr\PsrProcessor;

class DelegateProcessor implements PsrProcessor
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
    public function process(PsrMessage $message, PsrContext $context)
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
