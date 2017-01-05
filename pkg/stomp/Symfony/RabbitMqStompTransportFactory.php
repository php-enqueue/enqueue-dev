<?php

namespace Enqueue\Stomp\Symfony;

use Enqueue\Stomp\Client\ManagementClient;
use Enqueue\Stomp\Client\RabbitMqStompDriver;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class RabbitMqStompTransportFactory extends StompTransportFactory
{
    /**
     * @param string $name
     */
    public function __construct($name = 'rabbitmq_stomp')
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
                ->booleanNode('management_plugin_installed')
                    ->defaultFalse()
                    ->info('The option tells whether RabbitMQ broker has management plugin installed or not')
                ->end()
                ->integerNode('management_plugin_port')->min(1)->defaultValue(15672)->end()
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
        $management = new Definition(ManagementClient::class);
        $management->setFactory([ManagementClient::class, 'create']);
        $management->setArguments([
            $config['vhost'],
            $config['host'],
            $config['management_plugin_port'],
            $config['login'],
            $config['password'],
        ]);

        $managementId = sprintf('enqueue.client.%s.management_client', $this->getName());
        $container->setDefinition($managementId, $management);

        $driver = new Definition(RabbitMqStompDriver::class);
        $driver->setArguments([
            new Reference(sprintf('enqueue.transport.%s.context', $this->getName())),
            new Reference('enqueue.client.config'),
            new Reference('enqueue.client.meta.queue_meta_registry'),
            new Reference($managementId),
        ]);

        $driverId = sprintf('enqueue.client.%s.driver', $this->getName());
        $container->setDefinition($driverId, $driver);

        return $driverId;
    }
}
