<?php

namespace Enqueue\Stomp\Symfony;

use Enqueue\Stomp\Client\StompDriver;
use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Stomp\StompContext;
use Enqueue\Symfony\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class StompTransportFactory implements TransportFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'stomp')
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
                ->scalarNode('host')->defaultValue('localhost')->cannotBeEmpty()->end()
                ->integerNode('port')->min(1)->defaultValue(61613)->end()
                ->scalarNode('login')->defaultValue('guest')->cannotBeEmpty()->end()
                ->scalarNode('password')->defaultValue('guest')->cannotBeEmpty()->end()
                ->scalarNode('vhost')->defaultValue('/')->cannotBeEmpty()->end()
                ->booleanNode('sync')->defaultTrue()->end()
                ->integerNode('connection_timeout')->min(1)->defaultValue(1)->end()
                ->integerNode('buffer_size')->min(1)->defaultValue(1000)->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createContext(ContainerBuilder $container, array $config)
    {
        $factory = new Definition(StompConnectionFactory::class);
        $factory->setArguments([$config]);

        $factoryId = sprintf('enqueue.transport.%s.connection_factory', $this->getName());
        $container->setDefinition($factoryId, $factory);

        $context = new Definition(StompContext::class);
        $context->setFactory([new Reference($factoryId), 'createContext']);

        $contextId = sprintf('enqueue.transport.%s.context', $this->getName());
        $container->setDefinition($contextId, $context);

        return $contextId;
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver(ContainerBuilder $container, array $config)
    {
        $driver = new Definition(StompDriver::class);
        $driver->setArguments([
            new Reference(sprintf('enqueue.transport.%s.context', $this->getName())),
            new Reference('enqueue.client.config'),
        ]);

        $driverId = sprintf('enqueue.client.%s.driver', $this->getName());
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
