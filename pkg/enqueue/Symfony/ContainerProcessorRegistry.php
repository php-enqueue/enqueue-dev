<?php

namespace Enqueue\Symfony;

use Enqueue\ProcessorRegistryInterface;
use Interop\Queue\Processor;
use Psr\Container\ContainerInterface;

final class ContainerProcessorRegistry implements ProcessorRegistryInterface
{
    /**
     * @var ContainerInterface
     */
    private $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public function get(string $processorName): Processor
    {
        if (false == $this->locator->has($processorName)) {
            throw new \LogicException(sprintf('Service locator does not have a processor with name "%s".', $processorName));
        }

        return $this->locator->get($processorName);
    }
}
