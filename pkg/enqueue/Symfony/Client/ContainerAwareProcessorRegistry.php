<?php

namespace Enqueue\Symfony\Client;

use Enqueue\Client\ProcessorRegistryInterface;
use Interop\Queue\PsrProcessor;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\HttpKernel\Kernel;

class ContainerAwareProcessorRegistry implements ProcessorRegistryInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    /**
     * @var PsrProcessor[]
     */
    protected $processors;

    /**
     * @param array $processors
     */
    public function __construct(array $processors = [])
    {
        $this->processors = $processors;
    }

    /**
     * @param string $processorName
     * @param string $serviceId
     */
    public function set($processorName, $serviceId)
    {
        $this->processors[$processorName] = $serviceId;
    }

    /**
     * {@inheritdoc}
     */
    public function get($processorName)
    {
        if (30300 > Kernel::VERSION_ID) {
            // Symfony 3.2 and below make service identifiers lowercase, so we do the same.
            // To be removed when dropping support for Symfony < 3.3.
            $processorName = strtolower($processorName);
        }

        if (false == isset($this->processors[$processorName])) {
            throw new \LogicException(sprintf('Processor was not found. processorName: "%s"', $processorName));
        }

        if (null === $this->container) {
            throw new \LogicException('Container was not set');
        }

        $processor = $this->container->get($this->processors[$processorName]);

        if (false == $processor instanceof PsrProcessor) {
            throw new \LogicException(sprintf(
                'Invalid instance of message processor. expected: "%s", got: "%s"',
                PsrProcessor::class,
                is_object($processor) ? get_class($processor) : gettype($processor)
            ));
        }

        return $processor;
    }
}
