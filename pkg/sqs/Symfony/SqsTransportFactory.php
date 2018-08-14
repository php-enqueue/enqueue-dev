<?php

namespace Enqueue\Sqs\Symfony;

use Enqueue\Sqs\Client\SqsDriver;
use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\SqsContext;
use Enqueue\Symfony\DriverFactoryInterface;
use Enqueue\Symfony\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class SqsTransportFactory implements TransportFactoryInterface, DriverFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'sqs')
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
                ->scalarNode('client')->defaultNull()->end()
                ->scalarNode('key')->defaultNull()->end()
                ->scalarNode('secret')->defaultNull()->end()
                ->scalarNode('token')->defaultNull()->end()
                ->scalarNode('region')->end()
                ->integerNode('retries')->defaultValue(3)->end()
                ->scalarNode('version')->cannotBeEmpty()->defaultValue('2012-11-05')->end()
                ->booleanNode('lazy')
                    ->defaultTrue()
                    ->info('the connection will be performed as later as possible, if the option set to true')
                ->end()
                ->scalarNode('endpoint')->defaultNull()->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createConnectionFactory(ContainerBuilder $container, array $config)
    {
        $arguments = empty($config['client']) ? $config : new Reference($config['client']);

        $factory = new Definition(SqsConnectionFactory::class);
        $factory->setArguments([$arguments]);

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

        $context = new Definition(SqsContext::class);
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
        $driver = new Definition(SqsDriver::class);
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
