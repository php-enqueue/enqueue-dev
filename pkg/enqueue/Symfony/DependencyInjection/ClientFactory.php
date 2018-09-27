<?php

namespace Enqueue\Symfony\DependencyInjection;

use Enqueue\Client\DriverInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class ClientFactory
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('The name could not be empty.');
        }

        $this->name = $name;
    }

    public function createDriver(ContainerBuilder $container, array $config): string
    {
        $factoryId = sprintf('enqueue.transport.%s.connection_factory', $this->getName());
        $driverId = sprintf('enqueue.client.%s.driver', $this->getName());
        $driverFactoryId = sprintf('enqueue.client.%s.driver_factory', $this->getName());

        $container->register($driverId, DriverInterface::class)
            ->setFactory([new Reference($driverFactoryId), 'create'])
            ->addArgument(new Reference($factoryId))
            ->addArgument($config['dsn'])
            ->addArgument($config)
        ;

        return $driverId;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
