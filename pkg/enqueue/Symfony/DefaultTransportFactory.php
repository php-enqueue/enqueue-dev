<?php

namespace Enqueue\Symfony;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\Symfony\AmqpTransportFactory;
use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\Symfony\FsTransportFactory;
use Enqueue\Null\NullConnectionFactory;
use Enqueue\Null\Symfony\NullTransportFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use function Enqueue\dsn_to_connection_factory;

class DefaultTransportFactory implements TransportFactoryInterface
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
                ->ifString()
                ->then(function ($v) {
                    if (false === strpos($v, '://')) {
                        return ['alias' => $v];
                    }

                    return ['dsn' => $v];
                })
            ->end()
            ->children()
                ->scalarNode('alias')->cannotBeEmpty()->end()
                ->scalarNode('dsn')->cannotBeEmpty()->end()
            ;
    }

    public function createConnectionFactory(ContainerBuilder $container, array $config)
    {
        if (isset($config['alias'])) {
            $aliasId = sprintf('enqueue.transport.%s.connection_factory', $config['alias']);
        } elseif (isset($config['dsn'])) {
            $aliasId = $this->findFactory($config['dsn'])->createConnectionFactory($container, $config);
        } else {
            throw new \LogicException('Either dsn or alias option must be set.');
        }

        $factoryId = sprintf('enqueue.transport.%s.connection_factory', $this->getName());

        $container->setAlias($factoryId, $aliasId);
        $container->setAlias('enqueue.transport.connection_factory', $factoryId);

        return $factoryId;
    }

    /**
     * {@inheritdoc}
     */
    public function createContext(ContainerBuilder $container, array $config)
    {
        if (isset($config['alias'])) {
            $aliasId = sprintf('enqueue.transport.%s.context', $config['alias']);
        } elseif (isset($config['dsn'])) {
            $aliasId = $this->findFactory($config['dsn'])->createContext($container, $config);
        } else {
            throw new \LogicException('Either dsn or alias option must be set.');
        }

        $contextId = sprintf('enqueue.transport.%s.context', $this->getName());

        $container->setAlias($contextId, $aliasId);
        $container->setAlias('enqueue.transport.context', $contextId);

        return $contextId;
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver(ContainerBuilder $container, array $config)
    {
        if (isset($config['alias'])) {
            $aliasId = sprintf('enqueue.client.%s.driver', $config['alias']);
        } elseif (isset($config['dsn'])) {
            $aliasId = $this->findFactory($config['dsn'])->createDriver($container, $config);
        } else {
            throw new \LogicException('Either dsn or alias option must be set.');
        }

        $driverId = sprintf('enqueue.client.%s.driver', $this->getName());

        $container->setAlias($driverId, $aliasId);
        $container->setAlias('enqueue.client.driver', $driverId);

        return $driverId;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string
     * @param mixed $dsn
     *
     * @return TransportFactoryInterface
     */
    private function findFactory($dsn)
    {
        $connectionFactory = dsn_to_connection_factory($dsn);

        if ($connectionFactory instanceof AmqpConnectionFactory) {
            return new AmqpTransportFactory('default_amqp');
        }

        if ($connectionFactory instanceof FsConnectionFactory) {
            return new FsTransportFactory('default_fs');
        }

        if ($connectionFactory instanceof NullConnectionFactory) {
            return new NullTransportFactory('default_null');
        }

        throw new \LogicException(sprintf(
            'There is no supported transport factory for the connection factory "%s" created from DSN "%s"',
            get_class($connectionFactory),
            $dsn
        ));
    }
}
