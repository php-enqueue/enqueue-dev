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

    public function add(string $name, PsrProcessor $processor): void
    {
        $this->processors[$name] = $processor;
    }

    public function get(string $processorName): PsrProcessor
    {
        if (false == isset($this->processors[$processorName])) {
            throw new \LogicException(sprintf('Processor was not found. processorName: "%s"', $processorName));
        }

        return $this->processors[$processorName];
    }
}
