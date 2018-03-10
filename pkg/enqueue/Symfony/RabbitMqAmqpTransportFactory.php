<?php

namespace Enqueue\Symfony;

use Enqueue\AmqpTools\DelayStrategyTransportFactoryTrait;
use Enqueue\Client\Amqp\RabbitMqDriver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RabbitMqAmqpTransportFactory extends AmqpTransportFactory
{
    use DelayStrategyTransportFactoryTrait;

    /**
     * @param string $name
     */
    public function __construct($name = 'rabbitmq_amqp')
    {
        parent::__construct($name);
    }

    /**
     * {@inheritdoc}
     */
    public function addConfiguration(ArrayNodeDefinition $builder)
    {
        parent::addConfiguration($builder);

        $builder
            ->children()
                ->scalarNode('delay_strategy')
                    ->defaultValue('dlx')
                    ->info('The delay strategy to be used. Possible values are "dlx", "delayed_message_plugin" or service id')
                ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createConnectionFactory(ContainerBuilder $container, array $config)
    {
        $factoryId = parent::createConnectionFactory($container, $config);

        $this->registerDelayStrategy($container, $config, $factoryId, $this->getName());

        return $factoryId;
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver(ContainerBuilder $container, array $config)
    {
        $driver = new Definition(RabbitMqDriver::class);
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
}
