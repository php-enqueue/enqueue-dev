<?php

namespace Enqueue\Bundle\Tests\Unit\Mocks;

use Enqueue\Symfony\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class FooTransportFactory implements TransportFactoryInterface
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
    public function createContext(ContainerBuilder $container, array $config)
    {
        $connectionId = 'foo.context';

        $container->setDefinition($connectionId, new Definition(\stdClass::class, [$config]));

        return $connectionId;
    }

    public function createDriver(ContainerBuilder $container, array $config)
    {
        $driverId = 'foo.driver';

        $container->setDefinition($driverId, new Definition(\stdClass::class, [$config]));

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
