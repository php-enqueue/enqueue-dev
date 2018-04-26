<?php

namespace Enqueue\RdKafka\Symfony;

use Enqueue\RdKafka\Client\RdKafkaDriver;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\RdKafka\RdKafkaContext;
use Enqueue\Symfony\DriverFactoryInterface;
use Enqueue\Symfony\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RdKafkaTransportFactory implements TransportFactoryInterface, DriverFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'rdkafka')
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
            ->fixXmlConfig('topic')
            ->children()
                ->scalarNode('dsn')
                    ->info('The kafka DSN. Other parameters are ignored if set')
                ->end()
                ->arrayNode('global')
                    ->children()
                        ->scalarNode('metadata.broker.list')->end()
                    ->end()
                ->end()
                ->arrayNode('topics')
                    ->prototype('scalar')->end()
                ->end()
                ->scalarNode('dr_msq_cb')
                    ->info('todo')
                ->end()
                ->scalarNode('error_cb')
                    ->info('todo')
                ->end()
                ->scalarNode('rebalance_cb')
                    ->info('todo')
                ->end()
                ->enumNode('partitioner')
                    ->values(['RD_KAFKA_MSG_PARTITIONER_RANDOM', 'RD_KAFKA_MSG_PARTITIONER_CONSISTENT'])
                    ->info('todo')
                ->end()
                ->scalarNode('log_level')
                    ->info('todo')
                ->end()
                ->booleanNode('commit_async')
                    ->defaultFalse()
                    ->info('todo')
                ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createConnectionFactory(ContainerBuilder $container, array $config)
    {
        if (false == empty($config['rdkafka'])) {
            $config['rdkafka'] = new Reference($config['rdkafka']);
        }

        $factory = new Definition(RdKafkaConnectionFactory::class);
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

        $context = new Definition(RdKafkaContext::class);
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
        $driver = new Definition(RdKafkaDriver::class);
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
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }
}
