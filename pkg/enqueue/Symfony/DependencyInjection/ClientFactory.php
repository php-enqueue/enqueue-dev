<?php

namespace Enqueue\Symfony\DependencyInjection;

use Enqueue\Client\ChainExtension;
use Enqueue\Client\Config;
use Enqueue\Client\ConsumptionExtension\SetRouterPropertiesExtension;
use Enqueue\Client\DelegateProcessor;
use Enqueue\Client\DriverFactory;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Producer;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\RouteCollection;
use Enqueue\Client\RouterProcessor;
use Enqueue\Client\SpoolProducer;
use Enqueue\Consumption\ChainExtension as ConsumptionChainExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Rpc\RpcFactory;
use Enqueue\Symfony\ContainerProcessorRegistry;
use Interop\Queue\Context;
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

    public function build(ContainerBuilder $container, array $config): void
    {
        $container->register($this->format('context'), Context::class)
            ->setFactory([$this->reference('driver'), 'getContext'])
        ;

        $container->register($this->format('driver_factory'), DriverFactory::class)
            ->addArgument($this->reference('config'))
            ->addArgument($this->reference('route_collection'))
        ;

        $container->register($this->format('config'), Config::class);

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
            ->addArgument(null)
            ->addArgument(null)
            ->addArgument(null)
        ;

        $container->register($this->format('queue_consumer'), QueueConsumer::class)
            ->addArgument($this->reference('context'))
            ->addArgument($this->reference('consumption_extensions'))
            ->addArgument([])
            ->addArgument(new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE))
        ;

        $container->register($this->format('consumption_extensions'), ConsumptionChainExtension::class)
            ->addArgument([])
        ;

        if ('default' === $this->name) {
            $container->setAlias(ProducerInterface::class, $this->format('producer'))
                ->setPublic(true)
            ;

            $container->setAlias(SpoolProducer::class, $this->format('spool_producer'))
                ->setPublic(true)
            ;
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
