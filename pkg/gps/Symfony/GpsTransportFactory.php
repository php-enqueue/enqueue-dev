<?php

namespace Enqueue\Gps\Symfony;

use Enqueue\Gps\Client\GpsDriver;
use Enqueue\Gps\GpsConnectionFactory;
use Enqueue\Gps\GpsContext;
use Enqueue\Symfony\DriverFactoryInterface;
use Enqueue\Symfony\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class GpsTransportFactory implements TransportFactoryInterface, DriverFactoryInterface
{
    /**
     * @var string
     */
    private $name;

    /**
     * @param string $name
     */
    public function __construct($name = 'gps')
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
                    ->info('The connection to Google Pub/Sub broker set as a string. Other parameters are ignored if set')
                ->end()
                ->scalarNode('projectId')
                    ->info('The project ID from the Google Developer\'s Console.')
                ->end()
                ->scalarNode('keyFilePath')
                    ->info('The full path to your service account credentials.json file retrieved from the Google Developers Console.')
                ->end()
                ->integerNode('retries')
                    ->defaultValue(3)
                    ->info('Number of retries for a failed request.')
                ->end()
                ->arrayNode('scopes')
                    ->prototype('scalar')->end()
                    ->info('Scopes to be used for the request.')
                ->end()
                ->booleanNode('lazy')
                    ->defaultTrue()
                    ->info('The connection will be performed as later as possible, if the option set to true')
                ->end()
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function createConnectionFactory(ContainerBuilder $container, array $config)
    {
        foreach ($config as $key => $value) {
            if (null === $value) {
                unset($config[$key]);
            } elseif (is_array($value) && empty($value)) {
                unset($config[$key]);
            }
        }

        $factory = new Definition(GpsConnectionFactory::class);
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

        $context = new Definition(GpsContext::class);
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
        $driver = new Definition(GpsDriver::class);
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
