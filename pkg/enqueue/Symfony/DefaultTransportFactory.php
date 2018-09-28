<?php

namespace Enqueue\Symfony;

use Enqueue\Client\DriverInterface;
use Interop\Queue\Context;
use Interop\Queue\PsrConnectionFactory;
use Symfony\Component\Config\Definition\Builder\VariableNodeDefinition;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DefaultTransportFactory
{
    public function addConfiguration(VariableNodeDefinition $builder): void
    {
        $builder
            ->beforeNormalization()
                ->always(function ($v) {
                    if (empty($v)) {
                        return ['dsn' => 'null:'];
                    }

                    if (is_array($v)) {
                        return $v;
                    }

                    if (is_string($v)) {
                        return ['dsn' => $v];
                    }

                    throw new \LogicException(sprintf('The value must be array, null or string. Got "%s"', gettype($v)));
                })
            ->end()
            ->pro
            ->children()
                ->scalarNode('dsn')->cannotBeEmpty()->end()
                ->variableNode('config')->end()
            ->end()
        ->end()
        ;
    }

    public function createConnectionFactory(ContainerBuilder $container, array $config): string
    {
        $factoryId = sprintf('enqueue.transport.%s.connection_factory', $this->getName());

        $container->register($factoryId, PsrConnectionFactory::class)
            ->setFactory([new Reference('enqueue.connection_factory_factory'), 'create'])
            ->addArgument($config['dsn'])
        ;

        $container->setAlias('enqueue.transport.connection_factory', new Alias($factoryId, true));

        return $factoryId;
    }

    public function createContext(ContainerBuilder $container, array $config): string
    {
        $contextId = sprintf('enqueue.transport.%s.context', $this->getName());
        $factoryId = sprintf('enqueue.transport.%s.connection_factory', $this->getName());

        $container->register($contextId, Context::class)
            ->setFactory([new Reference($factoryId), 'createContext'])
        ;

        $container->setAlias('enqueue.transport.context', new Alias($contextId, true));

        return $contextId;
    }

    public function createDriver(ContainerBuilder $container, array $config): string
    {
        $factoryId = sprintf('enqueue.transport.%s.connection_factory', $this->getName());
        $driverId = sprintf('enqueue.client.%s.driver', $this->getName());

        $container->register($driverId, DriverInterface::class)
            ->setFactory([new Reference('enqueue.client.driver_factory'), 'create'])
            ->addArgument(new Reference($factoryId))
            ->addArgument($config['dsn'])
            ->addArgument($config)
        ;

        $container->setAlias('enqueue.client.driver', new Alias($driverId, true));

        return $driverId;
    }

    public function getName(): string
    {
        return 'default';
    }
}
