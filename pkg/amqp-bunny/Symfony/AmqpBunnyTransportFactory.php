<?php

namespace Enqueue\AmqpBunny\Symfony;

use Enqueue\AmqpBunny\AmqpConnectionFactory;
use Enqueue\AmqpBunny\AmqpContext;
use Enqueue\Client\Amqp\AmqpDriver;
use Enqueue\Symfony\DriverFactoryInterface;
use Enqueue\Symfony\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class AmqpBunnyTransportFactory implements TransportFactoryInterface, DriverFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'amqp_bunny')
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
                    ->info('The connection to AMQP broker set as a string. Other parameters are ignored if set')
                ->end()
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
                ->scalarNode('user')
                    ->defaultValue('guest')
                    ->cannotBeEmpty()
                    ->info('The user name to use. Note: Max 128 characters.')
                ->end()
                ->scalarNode('pass')
                    ->defaultValue('guest')
                    ->cannotBeEmpty()
                    ->info('Password. Note: Max 128 characters.')
                ->end()
                ->scalarNode('vhost')
                    ->defaultValue('/')
                    ->cannotBeEmpty()
                    ->info('The virtual host on the host. Note: Max 128 characters.')
                ->end()
                ->booleanNode('lazy')
                    ->defaultTrue()
                ->end()
                ->enumNode('receive_method')
                    ->values(['basic_get', 'basic_consume'])
                    ->defaultValue('basic_get')
                    ->info('The receive strategy to be used. We suggest to use basic_consume as it is more performant. Though you need AMQP extension 1.9.1 or higher')
                ->end()
                ->integerNode('heartbeat')
                    ->defaultValue(0)
                ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createConnectionFactory(ContainerBuilder $container, array $config)
    {
        $factory = new Definition(AmqpConnectionFactory::class);
        $factory->setArguments(isset($config['dsn']) ? [$config['dsn']] : [$config]);

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
