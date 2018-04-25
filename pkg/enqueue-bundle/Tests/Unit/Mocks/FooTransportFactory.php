<?php

namespace Enqueue\Bundle\Tests\Unit\Mocks;

use Enqueue\Symfony\DriverFactoryInterface;
use Enqueue\Symfony\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class FooTransportFactory implements TransportFactoryInterface, DriverFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'foo')
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('foo_param')->isRequired()->cannotBeEmpty()->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createConnectionFactory(ContainerBuilder $container, array $config)
    {
        $factoryId = 'foo.connection_factory';

        $container->setDefinition($factoryId, new Definition(\stdClass::class, [$config]));

        return $factoryId;
    }

    /**
     * {@inheritdoc}
     */
    public function createContext(ContainerBuilder $container, array $config)
    {
        $contextId = 'foo.context';

        $context = new Definition(\stdClass::class, [$config]);
        $context->setPublic(true);

        $container->setDefinition($contextId, $context);

        return $contextId;
    }

    public function createDriver(ContainerBuilder $container, array $config)
    {
        $driverId = 'foo.driver';

        $driver = new Definition(\stdClass::class, [$config]);
        $driver->setPublic(true);

        $container->setDefinition($driverId, $driver);

        return $driverId;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->name;
    }
}
