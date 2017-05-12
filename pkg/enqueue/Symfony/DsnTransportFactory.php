<?php

namespace Enqueue\Symfony;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DsnTransportFactory implements TransportFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * The key is a factory name
     *
     * @var TransportFactoryInterface[]
     */
    private $factories;

    /**
     * @param TransportFactoryInterface[] $factories
     * @param string $name
     */
    public function __construct(array $factories, $name = 'dsn')
    {
        $this->name = $name;

        $this->factories = [];
        foreach ($factories as $factory) {
            $this->factories[$factory->getName()] = $factory;
        }
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
                ->scalarNode('dsn')->isRequired()->cannotBeEmpty()->end()
        ;
    }

    public function createConnectionFactory(ContainerBuilder $container, array $config)
    {
        $factoryId = $this->findFactory($config['dsn'])->createConnectionFactory($container, [
            'dsn' => $config['dsn']
        ]);

        $container->setAlias(
            sprintf('enqueue.transport.%s.connection_factory', $this->getName()),
            $factoryId
        );

        return $factoryId;
    }

    /**
     * {@inheritdoc}
     */
    public function createContext(ContainerBuilder $container, array $config)
    {
        $contextId = $this->findFactory($config['dsn'])->createContext($container, [
            'dsn' => $config['dsn']
        ]);

        $container->setAlias(
            sprintf('enqueue.transport.%s.context', $this->getName()),
            $contextId
        );

        return $contextId;
    }

    /**
     * {@inheritdoc}
     */
    public function createDriver(ContainerBuilder $container, array $config)
    {
        $driverId = $this->findFactory($config['dsn'])->createDriver($container, [
            'dsn' => $config['dsn']
        ]);

        $container->setAlias(
            sprintf('enqueue.transport.%s.driver', $this->getName()),
            $driverId
        );

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
     *
     * @return TransportFactoryInterface
     */
    private function findFactory($dsn)
    {
        list($scheme) = explode('://', $dsn);

        if (false == $scheme || false === strpos($dsn, '://')) {
            throw new \LogicException(sprintf('The scheme could not be parsed from DSN "%s"', $dsn));
        }

        $supportedSchemes = ['amqp', 'rabbitmq_amqp', 'null'];
        if (false == in_array($scheme, $supportedSchemes)) {
            throw new \LogicException(sprintf('The scheme "%s" is not supported.', $scheme));
        }

        if (false == array_key_exists($scheme, $this->factories)) {
            throw new \LogicException(sprintf(
                'There is no factory that supports requested schema "%s", available are "%s"',
                $scheme,
                implode('", "', array_keys($this->factories))
            ));
        }

        return $this->factories[$scheme];
    }
}
