<?php

namespace Enqueue\Client;

use Enqueue\Psr\Processor;

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

    /**
     * @param string    $name
     * @param Processor $processor
     */
    public function add($name, Processor $processor)
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
