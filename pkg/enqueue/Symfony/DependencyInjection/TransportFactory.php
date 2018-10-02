<?php

namespace Enqueue\Symfony\DependencyInjection;

use Enqueue\ConnectionFactoryFactory;
use Enqueue\ConnectionFactoryFactoryInterface;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Resources;
use Enqueue\Rpc\RpcClient;
use Enqueue\Rpc\RpcFactory;
use Enqueue\Symfony\ContainerProcessorRegistry;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @internal
 */
final class TransportFactory
{
    use FormatTransportNameTrait;

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

    public function addConfiguration(ArrayNodeDefinition $builder): void
    {
        $knownSchemes = array_keys(Resources::getKnownSchemes());
        $availableSchemes = array_keys(Resources::getAvailableSchemes());

        $builder
            ->info('The transport option could accept a string DSN, an array with DSN key, or null. It accept extra options. To find out what option you can set, look at connection factory constructor docblock.')
            ->beforeNormalization()
                ->always(function ($v) {
                    if (empty($v)) {
                        return ['dsn' => 'null:'];
                    }

                    if (is_array($v)) {
                        if (isset($v['factory_class']) && isset($v['factory_service'])) {
                            throw new \LogicException('Both options factory_class and factory_service are set. Please choose one.');
                        }

                        if (isset($v['connection_factory_class']) && (isset($v['factory_class']) || isset($v['factory_service']))) {
                            throw new \LogicException('The option connection_factory_class must not be used with factory_class or factory_service at the same time. Please choose one.');
                        }

                        return $v;
                    }

                    if (is_string($v)) {
                        return ['dsn' => $v];
                    }

                    throw new \LogicException(sprintf('The value must be array, null or string. Got "%s"', gettype($v)));
                })
        ->end()
        ->ignoreExtraKeys(false)
        ->children()
            ->scalarNode('dsn')
                ->cannotBeEmpty()
                ->isRequired()
                ->info(sprintf(
                    'The MQ broker DSN. These schemes are supported: "%s", to use these "%s" you have to install a package.',
                    implode('", "', $knownSchemes),
                    implode('", "', $availableSchemes)
                ))
            ->end()
            ->scalarNode('connection_factory_class')
                ->info(sprintf('The connection factory class should implement "%s" interface', ConnectionFactory::class))
            ->end()
            ->scalarNode('factory_service')
                ->info(sprintf('The factory class should implement "%s" interface', ConnectionFactoryFactoryInterface::class))
            ->end()
            ->scalarNode('factory_class')
                ->info(sprintf('The factory service should be a class that implements "%s" interface', ConnectionFactoryFactoryInterface::class))
            ->end()
        ->end()
        ;
    }

    public function build(ContainerBuilder $container, array $config): void
    {
        $this->buildConnectionFactory($container, $config);
        $this->buildContext($container, $config);
        $this->buildQueueConsumer($container, $config);
        $this->buildRpcClient($container, $config);

        $container->register($this->format('processor_registry'), ContainerProcessorRegistry::class);

        $locator = 'enqueue.locator';
        if ($container->hasDefinition($locator)) {
            $locator = $container->getDefinition($locator);
            $locator->replaceArgument(0, array_merge($locator->getArgument(0), [
                $this->format('queue_consumer') => new Reference($this->format('queue_consumer')),
                $this->format('processor_registry') => new Reference($this->format('processor_registry')),
            ]));

            return;
        }

        if ('default' === $this->name) {
            $container->setAlias(RpcClient::class, $this->format('rpc_client'));
            $container->setAlias(ConnectionFactory::class, $this->format('connection_factory'));
            $container->setAlias(Context::class, $this->format('context'));
            $container->setAlias(QueueConsumerInterface::class, $this->format('queue_consumer'));
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    private function buildConnectionFactory(ContainerBuilder $container, array $config): void
    {
        $factoryId = $this->format('connection_factory');

        $factoryFactoryId = $this->format('connection_factory_factory');
        $container->register($factoryFactoryId, $config['factory_class'] ?? ConnectionFactoryFactory::class);

        $factoryFactoryService = new Reference(
            array_key_exists('factory_service', $config) ? $config['factory_service'] : $factoryFactoryId
        );

        unset($config['factory_service'], $config['factory_class']);

        if (array_key_exists('connection_factory_class', $config)) {
            $connectionFactoryClass = $config['connection_factory_class'];
            unset($config['connection_factory_class']);

            $container->register($factoryId, $connectionFactoryClass)
                ->addArgument($config)
            ;
        } else {
            $container->register($factoryId, ConnectionFactory::class)
                ->setFactory([$factoryFactoryService, 'create'])
                ->addArgument($config)
            ;
        }
    }

    private function buildContext(ContainerBuilder $container, array $config): void
    {
        $contextId = $this->format('context');
        $factoryId = $this->format('connection_factory');

        $container->register($contextId, Context::class)
            ->setFactory([new Reference($factoryId), 'createContext'])
        ;
    }

    private function buildQueueConsumer(ContainerBuilder $container, array $config): void
    {
        $container->setParameter($this->format('default_idle_time'), 0);
        $container->setParameter($this->format('default_receive_timeout'), 10000);

        $container->register($this->format('consumption_extensions'), ChainExtension::class)
            ->addArgument([])
        ;

        $container->register($this->format('queue_consumer'), QueueConsumer::class)
            ->addArgument(new Reference($this->format('context')))
            ->addArgument(new Reference($this->format('consumption_extensions')))
            ->addArgument($this->format('default_idle_time', true))
            ->addArgument($this->format('default_receive_timeout', true))
        ;
    }

    private function buildRpcClient(ContainerBuilder $container, array $config): void
    {
        $container->register($this->format('rpc_factory'), RpcFactory::class)
            ->addArgument(new Reference($this->format('context')))
        ;

        $container->register($this->format('rpc_client'), RpcClient::class)
            ->addArgument(new Reference($this->format('context')))
            ->addArgument(new Reference($this->format('rpc_factory')))
        ;
    }
}
