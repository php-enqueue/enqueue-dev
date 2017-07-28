<?php

namespace Enqueue\AmqpExt\Symfony;

use Enqueue\Client\Amqp\RabbitMqDriver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RabbitMqAmqpTransportFactory extends AmqpTransportFactory
{
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
                ->booleanNode('delay_plugin_installed')
                    ->defaultFalse()
                    ->info('The option tells whether RabbitMQ broker has delay plugin installed or not')
                ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver(ContainerBuilder $container, array $config)
    {
        $driver = new Definition(RabbitMqDriver::class);
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
