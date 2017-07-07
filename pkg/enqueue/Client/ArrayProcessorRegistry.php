<?php

namespace Enqueue\Client;

use Interop\Queue\PsrProcessor;

class ArrayProcessorRegistry implements ProcessorRegistryInterface
{
    /**
     * @var PsrProcessor[]
     */
    private $processors;

    /**
     * @param PsrProcessor[] $processors
     */
    public function __construct(array $processors = [])
    {
        $this->processors = $processors;
    }

    /**
     * @param string       $name
     * @param PsrProcessor $processor
     */
    public function add($name, PsrProcessor $processor)
    {
        $this->processors[$name] = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function get($processorName)
    {
        if (false == isset($this->processors[$processorName])) {
            throw new \LogicException(sprintf('Processor was not found. processorName: "%s"', $processorName));
        }

        return $this->processors[$processorName];
    }
}
