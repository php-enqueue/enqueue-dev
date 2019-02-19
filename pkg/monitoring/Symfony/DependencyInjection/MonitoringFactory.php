<?php

namespace Enqueue\Monitoring\Symfony\DependencyInjection;

use Enqueue\Monitoring\ClientMonitoringExtension;
use Enqueue\Monitoring\ConsumerMonitoringExtension;
use Enqueue\Monitoring\GenericStatsStorageFactory;
use Enqueue\Monitoring\Resources;
use Enqueue\Monitoring\StatsStorage;
use Enqueue\Monitoring\StatsStorageFactory;
use Enqueue\Symfony\DiUtils;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class MonitoringFactory
{
    public const MODULE = 'monitoring';

    /**
     * @var DiUtils
     */
    private $diUtils;

    public function __construct(string $name)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('The name could not be empty.');
        }

        $this->diUtils = DiUtils::create(self::MODULE, $name);
    }

    public static function getConfiguration(string $name = 'monitoring'): ArrayNodeDefinition
    {
        $builder = new ArrayNodeDefinition($name);

        $builder
            ->info(sprintf('The "%s" option could accept a string DSN, an array with DSN key, or null. It accept extra options. To find out what option you can set, look at stats storage constructor doc block.', $name))
            ->beforeNormalization()
                ->always(function ($v) {
                    if (\is_array($v)) {
                        if (isset($v['storage_factory_class'], $v['storage_factory_service'])) {
                            throw new \LogicException('Both options storage_factory_class and storage_factory_service are set. Please choose one.');
                        }

                        return $v;
                    }

                    if (is_string($v)) {
                        return ['dsn' => $v];
                    }

                    return $v;
                })
        ->end()
        ->ignoreExtraKeys(false)
        ->children()
            ->scalarNode('dsn')
                ->cannotBeEmpty()
                ->isRequired()
                ->info(sprintf('The stats storage DSN. These schemes are supported: "%s".', implode('", "', array_keys(Resources::getKnownSchemes()))))
            ->end()
            ->scalarNode('storage_factory_service')
                ->info(sprintf('The factory class should implement "%s" interface', StatsStorageFactory::class))
            ->end()
            ->scalarNode('storage_factory_class')
                ->info(sprintf('The factory service should be a class that implements "%s" interface', StatsStorageFactory::class))
            ->end()
        ->end()
        ;

        return $builder;
    }

    public function buildStorage(ContainerBuilder $container, array $config): void
    {
        $storageId = $this->diUtils->format('storage');
        $storageFactoryId = $this->diUtils->format('storage.factory');

        if (isset($config['storage_factory_service'])) {
            $container->setAlias($storageFactoryId, $config['storage_factory_service']);
        } elseif (isset($config['storage_factory_class'])) {
            $container->register($storageFactoryId, $config['storage_factory_class']);
        } else {
            $container->register($storageFactoryId, GenericStatsStorageFactory::class);
        }

        unset($config['storage_factory_service'], $config['storage_factory_class']);

        $container->register($storageId, StatsStorage::class)
            ->setFactory([new Reference($storageFactoryId), 'create'])
            ->addArgument($config)
        ;
    }

    public function buildClientExtension(ContainerBuilder $container, array $config): void
    {
        $container->register($this->diUtils->format('client_extension'), ClientMonitoringExtension::class)
            ->addArgument($this->diUtils->reference('storage'))
            ->addArgument(new Reference('logger'))
            ->addTag('enqueue.client_extension', ['client' => $this->diUtils->getConfigName()])
        ;
    }

    public function buildConsumerExtension(ContainerBuilder $container, array $config): void
    {
        $container->register($this->diUtils->format('consumer_extension'), ConsumerMonitoringExtension::class)
            ->addArgument($this->diUtils->reference('storage'))
            ->addTag('enqueue.consumption_extension', ['client' => $this->diUtils->getConfigName()])
            ->addTag('enqueue.transport.consumption_extension', ['transport' => $this->diUtils->getConfigName()])
        ;
    }
}
