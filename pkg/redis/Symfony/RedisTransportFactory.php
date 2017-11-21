<?php

namespace Enqueue\Redis\Symfony;

use Enqueue\Redis\Client\RedisDriver;
use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Redis\RedisContext;
use Enqueue\Symfony\DriverFactoryInterface;
use Enqueue\Symfony\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RedisTransportFactory implements TransportFactoryInterface, DriverFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'redis')
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
                ->ifTrue(function ($node) {
                    return empty($node['dsn']) && (empty($node['host']) || empty($node['vendor']));
                })
                ->thenInvalid('Invalid configuration %s')
            ->end()
            ->children()
                ->scalarNode('dsn')
                    ->info('The redis connection given as DSN. For example redis://host:port?vendor=predis')
                ->end()
                ->scalarNode('host')
                    ->cannotBeEmpty()
                    ->info('can be a host, or the path to a unix domain socket')
                ->end()
                ->integerNode('port')->end()
                ->enumNode('vendor')
                    ->values(['phpredis', 'predis'])
                    ->cannotBeEmpty()
                    ->info('The library used internally to interact with Redis server')
                ->end()
                ->booleanNode('persisted')
                    ->defaultFalse()
                    ->info('bool, Whether it use single persisted connection or open a new one for every context')
                ->end()
                ->booleanNode('lazy')
                    ->defaultTrue()
                    ->info('the connection will be performed as later as possible, if the option set to true')
                ->end()
                ->integerNode('database')
                    ->defaultValue(0)
                    ->info('Database index to select when connected.')
                ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createConnectionFactory(ContainerBuilder $container, array $config)
    {
        $factory = new Definition(RedisConnectionFactory::class);
        $factory->setArguments([isset($config['dsn']) ? $config['dsn'] : $config]);

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

        $context = new Definition(RedisContext::class);
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
        $driver = new Definition(RedisDriver::class);
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
