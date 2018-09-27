<?php

namespace Enqueue\Symfony\Client;

use Enqueue\Client\ProcessorRegistryInterface;
use Interop\Queue\PsrProcessor;
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

    public function get(string $processorName): PsrProcessor
    {
        if (false == $this->locator->has($processorName)) {
            throw new \LogicException(sprintf('Service locator does not have a processor with name "%s".', $processorName));
        }

        return $this->locator->get($processorName);
    }
}
