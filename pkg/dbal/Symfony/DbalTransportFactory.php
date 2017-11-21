<?php

namespace Enqueue\Dbal\Symfony;

use Enqueue\Dbal\Client\DbalDriver;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use Enqueue\Symfony\DriverFactoryInterface;
use Enqueue\Symfony\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class DbalTransportFactory implements TransportFactoryInterface, DriverFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'dbal')
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
                    ->info('The Doctrine DBAL DSN. Other parameters are ignored if set')
                ->end()
                ->variableNode('connection')
                    ->treatNullLike([])
                    ->info('Doctrine DBAL connection options. See http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html')
                ->end()
                ->scalarNode('dbal_connection_name')
                    ->defaultNull()
                    ->info('Doctrine dbal connection name.')
                ->end()
                ->scalarNode('table_name')
                    ->defaultValue('enqueue')
                    ->cannotBeEmpty()
                    ->info('Database table name.')
                ->end()
                ->integerNode('polling_interval')
                    ->defaultValue(1000)
                    ->min(100)
                    ->info('How often query for new messages.')
                ->end()
                ->booleanNode('lazy')
                    ->defaultTrue()
                ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createConnectionFactory(ContainerBuilder $container, array $config)
    {
        if (false == empty($config['dbal_connection_name'])) {
            $factory = new Definition(ManagerRegistryConnectionFactory::class);
            $factory->setArguments([new Reference('doctrine'), $config]);
        } elseif (false == empty($config['dsn'])) {
            $factory = new Definition(DbalConnectionFactory::class);
            $factory->setArguments([$config['dsn']]);
        } elseif (false == empty($config['connection'])) {
            $factory = new Definition(DbalConnectionFactory::class);
            $factory->setArguments([$config]);
        } else {
            throw new \LogicException('Set "dbal_connection_name" options when you want ot use doctrine registry, or use "connection" options to setup direct dbal connection.');
        }

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

        $context = new Definition(DbalContext::class);
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
        $driver = new Definition(DbalDriver::class);
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
