<?php

namespace Enqueue\AmqpExt\Symfony;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpExt\Client\AmqpDriver;
use Enqueue\Symfony\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AmqpTransportFactory implements TransportFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'amqp')
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
                ->scalarNode('host')
                    ->defaultValue('localhost')
                    ->cannotBeEmpty()
                    ->info('The host to connect too. Note: Max 1024 characters')
                ->end()
                ->scalarNode('port')
                    ->defaultValue(5672)
                    ->cannotBeEmpty()
                    ->info('Port on the host.')
                ->end()
                ->scalarNode('login')
                    ->defaultValue('guest')
                    ->cannotBeEmpty()
                    ->info('The login name to use. Note: Max 128 characters.')
                ->end()
                ->scalarNode('password')
                    ->defaultValue('guest')
                    ->cannotBeEmpty()
                    ->info('Password. Note: Max 128 characters.')
                ->end()
                ->scalarNode('vhost')
                    ->defaultValue('/')
                    ->cannotBeEmpty()
                    ->info('The virtual host on the host. Note: Max 128 characters.')
                ->end()
                ->integerNode('connect_timeout')
                    ->min(0)
                    ->info('Connection timeout. Note: 0 or greater seconds. May be fractional.')
                ->end()
                ->integerNode('read_timeout')
                    ->min(0)
                    ->info('Timeout in for income activity. Note: 0 or greater seconds. May be fractional.')
                ->end()
                ->integerNode('write_timeout')
                    ->min(0)
                    ->info('Timeout in for outcome activity. Note: 0 or greater seconds. May be fractional.')
                ->end()
                ->booleanNode('persisted')
                    ->defaultFalse()
                ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createContext(ContainerBuilder $container, array $config)
    {
        $factory = new Definition(AmqpConnectionFactory::class);
        $factory->setPublic(false);
        $factory->setArguments([$config]);

        $factoryId = sprintf('enqueue.transport.%s.connection_factory', $this->getName());
        $container->setDefinition($factoryId, $factory);

        $context = new Definition(AmqpContext::class);
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
        $driver = new Definition(AmqpDriver::class);
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
