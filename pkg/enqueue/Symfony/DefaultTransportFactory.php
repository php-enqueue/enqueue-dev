<?php

namespace Enqueue\Symfony;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DefaultTransportFactory implements TransportFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'default')
    {
        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        $builder
            ->beforeNormalization()
                ->ifString()
                ->then(function ($v) {
                    return ['alias' => $v];
                })
            ->end()
            ->children()
                ->scalarNode('alias')->isRequired()->cannotBeEmpty()->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createContext(ContainerBuilder $container, array $config)
    {
        $factoryId = sprintf('enqueue.transport.%s.connection_factory', $this->getName());
        $aliasId = sprintf('enqueue.transport.%s.connection_factory', $config['alias']);

        $container->setAlias($factoryId, $aliasId);
        $container->setAlias('enqueue.transport.connection_factory', $factoryId);

        $contextId = sprintf('enqueue.transport.%s.context', $this->getName());
        $aliasId = sprintf('enqueue.transport.%s.context', $config['alias']);

        $container->setAlias($contextId, $aliasId);
        $container->setAlias('enqueue.transport.context', $contextId);

        return $contextId;
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver(ContainerBuilder $container, array $config)
    {
        $driverId = sprintf('enqueue.client.%s.driver', $this->getName());
        $aliasId = sprintf('enqueue.client.%s.driver', $config['alias']);

        $container->setAlias($driverId, $aliasId);
        $container->setAlias('enqueue.client.driver', $driverId);

        return $driverId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
