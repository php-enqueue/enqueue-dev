<?php

namespace Enqueue;

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
        $this->processors = [];
        array_walk($processors, function (Processor $processor, string $key) {
            $this->processors[$key] = $processor;
        });
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
