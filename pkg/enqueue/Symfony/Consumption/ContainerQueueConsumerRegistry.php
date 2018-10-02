<?php

declare(strict_types=1);

namespace Enqueue\Symfony\Consumption;

use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Consumption\QueueConsumerRegistryInterface;
use Psr\Container\ContainerInterface;

final class ContainerQueueConsumerRegistry implements QueueConsumerRegistryInterface
{
    /**
     * @var ContainerInterface
     */
    private $locator;

    public function __construct(ContainerInterface $locator)
    {
        $this->locator = $locator;
    }

    public function get(string $name): QueueConsumerInterface
    {
        if (false == $this->locator->has($name)) {
            throw new \LogicException(sprintf('Service locator does not have a queue consumer with name "%s".', $name));
        }

        return $this->locator->get($name);
    }
}
