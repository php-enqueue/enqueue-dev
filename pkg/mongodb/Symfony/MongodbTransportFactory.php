<?php

namespace Enqueue\Mongodb\Symfony;

use Enqueue\Mongodb\Client\MongodbDriver;
use Enqueue\Mongodb\MongodbConnectionFactory;
use Enqueue\Mongodb\MongodbContext;
use Enqueue\Symfony\DriverFactoryInterface;
use Enqueue\Symfony\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class MongodbTransportFactory implements TransportFactoryInterface, DriverFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'mongodb')
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
                    return ['dsn' => $v];
                })
            ->end()
            ->children()
                ->scalarNode('dsn')
                    ->info('The Mongodb DSN. Other parameters are ignored if set')
                    ->isRequired()
                ->end()
                ->scalarNode('dbname')
                    ->defaultValue('enqueue')
                    ->info('Database name.')
                ->end()
                ->scalarNode('collection_name')
                    ->defaultValue('enqueue')
                    ->info('Collection')
                ->end()
                ->integerNode('polling_interval')
                    ->defaultValue(1000)
                    ->min(100)
                    ->info('How often query for new messages.')
                ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createConnectionFactory(ContainerBuilder $container, array $config)
    {
        $factory = new Definition(MongodbConnectionFactory::class, [$config]);

        $factoryId = sprintf('enqueue.transport.%s.connection_factory', $this->getName());
        $container->setDefinition($factoryId, $factory);

        return $factoryId;
    }

    /**
     * {@inheritdoc}
     */
    public function createContext(ContainerBuilder $container, array $config)
    {
        $factoryId = sprintf('enqueue.transport.%s.connection_factory', $this->getName());

        $context = new Definition(MongodbContext::class);
        $context->setPublic(true);
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
        $driver = new Definition(MongodbDriver::class);
        $driver->setPublic(true);
        $driver->setArguments([
            new Reference(sprintf('enqueue.transport.%s.context', $this->getName())),
            new Reference('enqueue.client.config'),
            new Reference('enqueue.client.meta.queue_meta_registry'),
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
