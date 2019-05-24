<?php

namespace Enqueue\Symfony\Client\DependencyInjection;

use Enqueue\Client\ChainExtension;
use Enqueue\Client\Config;
use Enqueue\Client\ConsumptionExtension\DelayRedeliveredMessageExtension;
use Enqueue\Client\ConsumptionExtension\ExclusiveCommandExtension;
use Enqueue\Client\ConsumptionExtension\FlushSpoolProducerExtension;
use Enqueue\Client\ConsumptionExtension\SetRouterPropertiesExtension;
use Enqueue\Client\DelegateProcessor;
use Enqueue\Client\DriverFactory;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Producer;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\RouteCollection;
use Enqueue\Client\RouterProcessor;
use Enqueue\Client\SpoolProducer;
use Enqueue\Client\TraceableProducer;
use Enqueue\Consumption\ChainExtension as ConsumptionChainExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Rpc\RpcFactory;
use Enqueue\Symfony\Client\FlushSpoolProducerListener;
use Enqueue\Symfony\Client\LazyProducer;
use Enqueue\Symfony\ContainerProcessorRegistry;
use Enqueue\Symfony\DependencyInjection\TransportFactory;
use Enqueue\Symfony\DiUtils;
use Interop\Queue\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class ClientFactory
{
    public const MODULE = 'client';

    /**
     * @var bool
     */
    private $default;

    /**
     * @var DiUtils
     */
    private $diUtils;

    public function __construct(string $name, bool $default = false)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('The name could not be empty.');
        }

        $this->default = $default;
        $this->diUtils = DiUtils::create(self::MODULE, $name);
    }

    public static function getConfiguration(bool $debug, string $name = 'client'): NodeDefinition
    {
        $builder = new ArrayNodeDefinition($name);

        $builder->children()
            ->booleanNode('traceable_producer')->defaultValue($debug)->end()
            ->scalarNode('prefix')->defaultValue('enqueue')->end()
            ->scalarNode('separator')->defaultValue('.')->end()
            ->scalarNode('app_name')->defaultValue('app')->end()
            ->scalarNode('router_topic')->defaultValue('default')->cannotBeEmpty()->end()
            ->scalarNode('router_queue')->defaultValue('default')->cannotBeEmpty()->end()
            ->scalarNode('router_processor')->defaultNull()->end()
            ->integerNode('redelivered_delay_time')->min(0)->defaultValue(0)->end()
            ->scalarNode('default_queue')->defaultValue('default')->cannotBeEmpty()->end()
            ->arrayNode('driver_options')
                ->addDefaultsIfNotSet()
                ->info('The array contains driver specific options')
                ->ignoreExtraKeys(false)
            ->end()
            ->end()->end()
        ;

        return $builder;
    }

    public function build(ContainerBuilder $container, array $config): void
    {
        $container->register($this->diUtils->format('context'), Context::class)
            ->setFactory([$this->diUtils->reference('driver'), 'getContext'])
        ;

        $container->register($this->diUtils->format('driver_factory'), DriverFactory::class);

        $routerProcessor = empty($config['router_processor'])
            ? $this->diUtils->format('router_processor')
            : $config['router_processor']
        ;

        $container->register($this->diUtils->format('config'), Config::class)
            ->setArguments([
                $config['prefix'],
                $config['separator'],
                $config['app_name'],
                $config['router_topic'],
                $config['router_queue'],
                $config['default_queue'],
                $routerProcessor,
                $config['transport'],
                $config['driver_options'] ?? [],
            ]);

        $container->setParameter($this->diUtils->format('router_processor'), $routerProcessor);
        $container->setParameter($this->diUtils->format('router_queue_name'), $config['router_queue']);
        $container->setParameter($this->diUtils->format('default_queue_name'), $config['default_queue']);

        $container->register($this->diUtils->format('route_collection'), RouteCollection::class)
            ->addArgument([])
            ->setFactory([RouteCollection::class, 'fromArray'])
        ;

        $container->register($this->diUtils->format('producer'), Producer::class)
            // @deprecated
            ->setPublic(true)
            ->addArgument($this->diUtils->reference('driver'))
            ->addArgument($this->diUtils->reference('rpc_factory'))
            ->addArgument($this->diUtils->reference('client_extensions'))
        ;

        $lazyProducer = $container->register($this->diUtils->format('lazy_producer'), LazyProducer::class);
        $lazyProducer->addArgument(ServiceLocatorTagPass::register($container, [
            $this->diUtils->format('producer') => new Reference($this->diUtils->format('producer')),
        ]));
        $lazyProducer->addArgument($this->diUtils->format('producer'));

        $container->register($this->diUtils->format('spool_producer'), SpoolProducer::class)
            ->addArgument($this->diUtils->reference('lazy_producer'))
        ;

        $container->register($this->diUtils->format('client_extensions'), ChainExtension::class)
            ->addArgument([])
        ;

        $container->register($this->diUtils->format('rpc_factory'), RpcFactory::class)
            ->addArgument($this->diUtils->reference('context'))
        ;

        $container->register($this->diUtils->format('router_processor'), RouterProcessor::class)
            ->addArgument($this->diUtils->reference('driver'))
        ;

        $container->register($this->diUtils->format('processor_registry'), ContainerProcessorRegistry::class);

        $container->register($this->diUtils->format('delegate_processor'), DelegateProcessor::class)
            ->addArgument($this->diUtils->reference('processor_registry'))
        ;

        $container->register($this->diUtils->format('set_router_properties_extension'), SetRouterPropertiesExtension::class)
            ->addArgument($this->diUtils->reference('driver'))
            ->addTag('enqueue.consumption_extension', ['priority' => 100, 'client' => $this->diUtils->getConfigName()])
        ;

        $container->register($this->diUtils->format('queue_consumer'), QueueConsumer::class)
            ->addArgument($this->diUtils->reference('context'))
            ->addArgument($this->diUtils->reference('consumption_extensions'))
            ->addArgument([])
            ->addArgument(new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            ->addArgument($config['consumption']['receive_timeout'])
        ;

        $container->register($this->diUtils->format('consumption_extensions'), ConsumptionChainExtension::class)
            ->addArgument([])
        ;

        $container->register($this->diUtils->format('flush_spool_producer_extension'), FlushSpoolProducerExtension::class)
            ->addArgument($this->diUtils->reference('spool_producer'))
            ->addTag('enqueue.consumption.extension', ['priority' => -100, 'client' => $this->diUtils->getConfigName()])
        ;

        $container->register($this->diUtils->format('exclusive_command_extension'), ExclusiveCommandExtension::class)
            ->addArgument($this->diUtils->reference('driver'))
            ->addTag('enqueue.consumption.extension', ['priority' => 100, 'client' => $this->diUtils->getConfigName()])
        ;

        if ($config['traceable_producer']) {
            $container->register($this->diUtils->format('traceable_producer'), TraceableProducer::class)
                ->setDecoratedService($this->diUtils->format('producer'))
                ->addArgument($this->diUtils->reference('traceable_producer.inner'))
            ;
        }

        if ($config['redelivered_delay_time']) {
            $container->register($this->diUtils->format('delay_redelivered_message_extension'), DelayRedeliveredMessageExtension::class)
                ->addArgument($this->diUtils->reference('driver'))
                ->addArgument($config['redelivered_delay_time'])
                ->addTag('enqueue.consumption_extension', ['priority' => 10, 'client' => $this->diUtils->getConfigName()])
            ;

            $container->getDefinition($this->diUtils->format('delay_redelivered_message_extension'))
                ->replaceArgument(1, $config['redelivered_delay_time'])
            ;
        }

        $locatorId = 'enqueue.locator';
        if ($container->hasDefinition($locatorId)) {
            $locator = $container->getDefinition($locatorId);
            $locator->replaceArgument(0, array_replace($locator->getArgument(0), [
                $this->diUtils->format('queue_consumer') => $this->diUtils->reference('queue_consumer'),
                $this->diUtils->format('driver') => $this->diUtils->reference('driver'),
                $this->diUtils->format('delegate_processor') => $this->diUtils->reference('delegate_processor'),
                $this->diUtils->format('producer') => $this->diUtils->reference('lazy_producer'),
            ]));
        }

        if ($this->default) {
            $container->setAlias(ProducerInterface::class, $this->diUtils->format('lazy_producer'));
            $container->setAlias(SpoolProducer::class, $this->diUtils->format('spool_producer'));

            if (DiUtils::DEFAULT_CONFIG !== $this->diUtils->getConfigName()) {
                $container->setAlias($this->diUtils->formatDefault('producer'), $this->diUtils->format('producer'));
                $container->setAlias($this->diUtils->formatDefault('spool_producer'), $this->diUtils->format('spool_producer'));
            }
        }
    }

    public function createDriver(ContainerBuilder $container, array $config): string
    {
        $factoryId = DiUtils::create(TransportFactory::MODULE, $this->diUtils->getConfigName())->format('connection_factory');
        $driverId = $this->diUtils->format('driver');
        $driverFactoryId = $this->diUtils->format('driver_factory');

        $container->register($driverId, DriverInterface::class)
            ->setFactory([new Reference($driverFactoryId), 'create'])
            ->addArgument(new Reference($factoryId))
            ->addArgument($this->diUtils->reference('config'))
            ->addArgument($this->diUtils->reference('route_collection'))
        ;

        if ($this->default) {
            $container->setAlias(DriverInterface::class, $driverId);

            if (DiUtils::DEFAULT_CONFIG !== $this->diUtils->getConfigName()) {
                $container->setAlias($this->diUtils->formatDefault('driver'), $driverId);
            }
        }

        return $driverId;
    }

    public function createFlushSpoolProducerListener(ContainerBuilder $container): void
    {
        $container->register($this->diUtils->format('flush_spool_producer_listener'), FlushSpoolProducerListener::class)
            ->addArgument($this->diUtils->reference('spool_producer'))
            ->addTag('kernel.event_subscriber')
        ;
    }
}
