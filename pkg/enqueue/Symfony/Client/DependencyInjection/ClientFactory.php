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
use Enqueue\Symfony\ContainerProcessorRegistry;
use Enqueue\Symfony\DependencyInjection\FormatClientNameTrait;
use Interop\Queue\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class ClientFactory
{
    use FormatClientNameTrait;

    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('The name could not be empty.');
        }

        $this->name = $name;
    }

    public function addClientConfiguration(ArrayNodeDefinition $builder, bool $debug): void
    {
        $builder->children()
            ->booleanNode('traceable_producer')->defaultValue($debug)->end()
            ->scalarNode('prefix')->defaultValue('enqueue')->end()
            ->scalarNode('app_name')->defaultValue('app')->end()
            ->scalarNode('router_topic')->defaultValue('default')->cannotBeEmpty()->end()
            ->scalarNode('router_queue')->defaultValue('default')->cannotBeEmpty()->end()
            ->scalarNode('router_processor')->defaultValue($this->format('router_processor'))->end()
            ->scalarNode('default_processor_queue')->defaultValue('default')->cannotBeEmpty()->end()
            ->integerNode('redelivered_delay_time')->min(0)->defaultValue(0)->end()
        ->end()->end()
        ;
    }

    public function build(ContainerBuilder $container, array $config): void
    {
        $container->register($this->format('context'), Context::class)
            ->setFactory([$this->reference('driver'), 'getContext'])
        ;

        $container->register($this->format('driver_factory'), DriverFactory::class)
            ->addArgument($this->reference('config'))
            ->addArgument($this->reference('route_collection'))
        ;

        $container->register($this->format('config'), Config::class)
            ->setArguments([
                $config['prefix'],
                $config['app_name'],
                $config['router_topic'],
                $config['router_queue'],
                $config['default_processor_queue'],
                $config['router_processor'],
                // @todo should be driver options.
                $config['transport'],
            ]);

        $container->setParameter($this->format('router_processor'), $config['router_processor']);
        $container->setParameter($this->format('router_queue_name'), $config['router_queue']);
        $container->setParameter($this->format('default_queue_name'), $config['default_processor_queue']);

        $container->register($this->format('route_collection'), RouteCollection::class)
            ->addArgument([])
            ->setFactory([RouteCollection::class, 'fromArray'])
        ;

        $container->register($this->format('producer'), Producer::class)
            ->addArgument($this->reference('driver'))
            ->addArgument($this->reference('rpc_factory'))
            ->addArgument($this->reference('client_extensions'))
        ;

        $container->register($this->format('spool_producer'), SpoolProducer::class)
            ->addArgument($this->reference('producer'))
        ;

        $container->register($this->format('client_extensions'), ChainExtension::class)
            ->addArgument([])
        ;

        $container->register($this->format('rpc_factory'), RpcFactory::class)
            ->addArgument($this->reference('context'))
        ;

        $container->register($this->format('router_processor'), RouterProcessor::class)
            ->addArgument($this->reference('driver'))
        ;

        $container->register($this->format('processor_registry'), ContainerProcessorRegistry::class);

        $container->register($this->format('delegate_processor'), DelegateProcessor::class)
            ->addArgument($this->reference('processor_registry'))
        ;

        $container->register($this->format('set_router_properties_extension'), SetRouterPropertiesExtension::class)
            ->addArgument($this->reference('driver'))
            ->addTag('enqueue.consumption_extension', ['priority' => 100, 'client' => $this->name])
        ;

        $container->register($this->format('queue_consumer'), QueueConsumer::class)
            ->addArgument($this->reference('context'))
            ->addArgument($this->reference('consumption_extensions'))
            ->addArgument([])
            ->addArgument($this->reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE))
            ->addArgument($config['consumption']['receive_timeout'])
        ;

        $container->register($this->format('consumption_extensions'), ConsumptionChainExtension::class)
            ->addArgument([])
        ;

        $container->register($this->format('flush_spool_producer_extension'), FlushSpoolProducerExtension::class)
            ->addArgument($this->reference('spool_producer'))
            ->addTag('enqueue.consumption.extension', ['priority' => -100, 'client' => $this->name])
        ;

        $container->register($this->format('exclusive_command_extension'), ExclusiveCommandExtension::class)
            ->addArgument($this->reference('driver'))
            ->addTag('enqueue.consumption.extension', ['priority' => 100, 'client' => $this->name])
        ;

        if ($config['traceable_producer']) {
            $container->register($this->format('traceable_producer'), TraceableProducer::class)
                ->setDecoratedService($this->format('producer'))
                ->addArgument($this->reference('traceable_producer.inner'))
            ;
        }

        if ($config['redelivered_delay_time']) {
            $container->register($this->format('delay_redelivered_message_extension'), DelayRedeliveredMessageExtension::class)
                ->addArgument($this->reference('driver'))
                ->addArgument($config['redelivered_delay_time'])
                ->addTag('enqueue.consumption_extension', ['priority' => 10, 'client' => $this->name])
            ;

            $container->getDefinition('enqueue.client.default.delay_redelivered_message_extension')
                ->replaceArgument(1, $config['redelivered_delay_time'])
            ;
        }

        $locatorId = 'enqueue.locator';
        if ($container->hasDefinition($locatorId)) {
            $locator = $container->getDefinition($locatorId);
            $locator->replaceArgument(0, array_replace($locator->getArgument(0), [
                $this->format('queue_consumer') => $this->reference('queue_consumer'),
                $this->format('driver') => $this->reference('driver'),
                $this->format('delegate_processor') => $this->reference('delegate_processor'),
                $this->format('producer') => $this->reference('producer'),
            ]));
        }

        if ('default' === $this->name) {
            $container->setAlias(ProducerInterface::class, $this->format('producer'));

            $container->setAlias(SpoolProducer::class, $this->format('spool_producer'));
        }
    }

    public function createDriver(ContainerBuilder $container, array $config): string
    {
        $factoryId = sprintf('enqueue.transport.%s.connection_factory', $this->getName());
        $driverId = sprintf('enqueue.client.%s.driver', $this->getName());
        $driverFactoryId = sprintf('enqueue.client.%s.driver_factory', $this->getName());

        $container->register($driverId, DriverInterface::class)
            ->setFactory([new Reference($driverFactoryId), 'create'])
            ->addArgument(new Reference($factoryId))
            ->addArgument($config['dsn'])
            ->addArgument($config)
        ;

        return $driverId;
    }

    public function getName(): string
    {
        return $this->name;
    }
}
