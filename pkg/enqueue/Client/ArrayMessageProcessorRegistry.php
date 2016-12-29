<?php
namespace Enqueue\Client;

use Enqueue\Consumption\MessageProcessorInterface;

class ArrayMessageProcessorRegistry implements MessageProcessorRegistryInterface
{
    /**
     * @var MessageProcessorInterface[]
     */
    private $processors;

    /**
     * @param MessageProcessorInterface[] $processors
     */
    public function __construct(array $processors = [])
    {
        $this->processors = $processors;
    }

    /**
     * @param string                    $name
     * @param MessageProcessorInterface $processor
     */
    public function add($name, MessageProcessorInterface $processor)
    {
        $this->processors[$name] = $processor;
    }

    /**
     * {@inheritdoc}
     */
    public function get($processorName)
    {
        if (false == isset($this->processors[$processorName])) {
            throw new \LogicException(sprintf('MessageProcessor was not found. processorName: "%s"', $processorName));
        }

        return $this->processors[$processorName];
    }
}
