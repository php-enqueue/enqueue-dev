<?php

namespace Enqueue\Client;

use Interop\Queue\Processor;

class ArrayProcessorRegistry implements ProcessorRegistryInterface
{
    /**
     * @var Processor[]
     */
    private $processors;

    /**
     * @param Processor[] $processors
     */
    public function __construct(array $processors = [])
    {
        $this->processors = $processors;
    }

    public function add(string $name, Processor $processor): void
    {
        $this->processors[$name] = $processor;
    }

    public function get(string $processorName): Processor
    {
        if (false == isset($this->processors[$processorName])) {
            throw new \LogicException(sprintf('Processor was not found. processorName: "%s"', $processorName));
        }

        return $this->processors[$processorName];
    }
}
