<?php

namespace Enqueue\Symfony;

use Enqueue\Client\DriverInterface;
use Interop\Queue\PsrConnectionFactory;
use Interop\Queue\PsrContext;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DefaultTransportFactory implements TransportFactoryInterface, DriverFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'default')
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
                ->always(function ($v) {
                    if (is_array($v)) {
                        if (empty($v['dsn']) && empty($v['alias'])) {
                            throw new \LogicException('Either dsn or alias option must be set');
                        }

                        return $v;
                    }

                    if (empty($v)) {
                        return ['dsn' => 'null:'];
                    }

                    if (is_string($v)) {
                        return false !== strpos($v, ':') || false !== strpos($v, 'env_') ?
                            ['dsn' => $v] :
                            ['alias' => $v]
                        ;
                    }

                    throw new \LogicException(sprintf('The value must be array, null or string. Got "%s"', gettype($v)));
                })
            ->end()
            ->children()
                ->scalarNode('alias')->cannotBeEmpty()->end()
                ->scalarNode('dsn')->cannotBeEmpty()->end()
            ->end()
        ->end()
        ;
    }

    public function createConnectionFactory(ContainerBuilder $container, array $config)
    {
        $factoryId = sprintf('enqueue.transport.%s.connection_factory', $this->getName());
        if (isset($config['alias'])) {
            $aliasId = sprintf('enqueue.transport.%s.connection_factory', $config['alias']);
            $container->setAlias($factoryId, new Alias($aliasId, true));
        } else {
            $container->register($factoryId, PsrConnectionFactory::class)
                ->setFactory([new Reference('enqueue.connection_factory_factory'), 'create'])
                ->addArgument($config['dsn'])
            ;
        }

        $container->setAlias('enqueue.transport.connection_factory', new Alias($factoryId, true));

        return $factoryId;
    }

    /**
     * {@inheritdoc}
     */
    public function createContext(ContainerBuilder $container, array $config)
    {
        $contextId = sprintf('enqueue.transport.%s.context', $this->getName());
        $factoryId = sprintf('enqueue.transport.%s.connection_factory', $this->getName());

        if (isset($config['alias'])) {
            $aliasId = sprintf('enqueue.transport.%s.context', $config['alias']);
            $container->setAlias($contextId, new Alias($aliasId, true));
        } else {
            $container->register($contextId, PsrContext::class)
                ->setFactory([new Reference($factoryId), 'createContext'])
            ;
        }

        $container->setAlias('enqueue.transport.context', new Alias($contextId, true));

        return $contextId;
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver(ContainerBuilder $container, array $config)
    {
        $factoryId = sprintf('enqueue.transport.%s.connection_factory', $this->getName());
        $driverId = sprintf('enqueue.client.%s.driver', $this->getName());

        if (isset($config['alias'])) {
            $aliasId = sprintf('enqueue.client.%s.driver', $config['alias']);
            $container->setAlias($driverId, new Alias($aliasId, true));
        } else {
            $container->register($driverId, DriverInterface::class)
                ->setFactory([new Reference('enqueue.client.driver_factory'), 'create'])
                ->addArgument(new Reference($factoryId))
                ->addArgument($config['dsn'])
                ->addArgument($config)
            ;
        }

        $container->setAlias('enqueue.client.driver', new Alias($driverId, true));

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
