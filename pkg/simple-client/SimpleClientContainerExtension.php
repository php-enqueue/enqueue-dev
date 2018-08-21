<?php

namespace Enqueue\SimpleClient;

use Enqueue\Client\ArrayProcessorRegistry;
use Enqueue\Client\Config;
use Enqueue\Client\ConsumptionExtension\DelayRedeliveredMessageExtension;
use Enqueue\Client\ConsumptionExtension\SetRouterPropertiesExtension;
use Enqueue\Client\DelegateProcessor;
use Enqueue\Client\DriverFactory;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Client\Meta\TopicMetaRegistry;
use Enqueue\Client\Producer;
use Enqueue\Client\RouterProcessor;
use Enqueue\ConnectionFactoryFactory;
use Enqueue\Consumption\ChainExtension as ConsumptionChainExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Rpc\RpcFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class SimpleClientContainerExtension extends Extension
{
    /**
     * @var TransportFactoryInterface[]
     */
    private $factories;

    public function __construct()
    {
        $this->factories = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getAlias()
    {
        return 'enqueue';
    }

    /**
     * @param TransportFactoryInterface $transportFactory
     */
    public function addTransportFactory(TransportFactoryInterface $transportFactory)
    {
        $name = $transportFactory->getName();

        if (empty($name)) {
            throw new \LogicException('Transport factory name cannot be empty');
        }
        if (array_key_exists($name, $this->factories)) {
            throw new \LogicException(sprintf('Transport factory with such name already added. Name %s', $name));
        }

        $this->factories[$name] = $transportFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configProcessor = new Processor();
        $config = $configProcessor->process($this->createConfiguration(), $configs);

        foreach ($config['transport'] as $name => $transportConfig) {
            $this->factories[$name]->createConnectionFactory($container, $transportConfig);
            $this->factories[$name]->createContext($container, $transportConfig);
            $this->factories[$name]->createDriver($container, $transportConfig);
        }

        $transportConfig = isset($config['transport']['default']['alias']) ?
            $config['transport'][$config['transport']['default']['alias']] :
            []
        ;

        $container->register('enqueue.client.config', Config::class)
            ->setPublic(true)
            ->setArguments([
                $config['client']['prefix'],
                $config['client']['app_name'],
                $config['client']['router_topic'],
                $config['client']['router_queue'],
                $config['client']['default_processor_queue'],
                'enqueue.client.router_processor',
                $transportConfig,
        ]);

        $container->register('enqueue.connection_factory_factory', ConnectionFactoryFactory::class);

        $container->register('enqueue.client.driver_factory', DriverFactory::class)
            ->addArgument(new Reference('enqueue.client.config'))
            ->addArgument(new Reference('enqueue.client.meta.queue_meta_registry'))
        ;

        $container->register('enqueue.client.rpc_factory', RpcFactory::class)
            ->setPublic(true)
            ->setArguments([
                new Reference('enqueue.transport.context'),
            ])
        ;

        $container->register('enqueue.client.producer', Producer::class)
            ->setPublic(true)
            ->setArguments([
                new Reference('enqueue.client.driver'),
                new Reference('enqueue.client.rpc_factory'),
            ])
        ;

        $container->setAlias('enqueue.client.producer_v2', new Alias('enqueue.client.producer', true));

        $container->register('enqueue.client.meta.topic_meta_registry', TopicMetaRegistry::class)
            ->setPublic(true)
            ->setArguments([[]]);

        $container->register('enqueue.client.meta.queue_meta_registry', QueueMetaRegistry::class)
            ->setPublic(true)
            ->setArguments([new Reference('enqueue.client.config'), []]);

        $container->register('enqueue.client.processor_registry', ArrayProcessorRegistry::class)
            ->setPublic(true)
        ;

        $container->register('enqueue.client.delegate_processor', DelegateProcessor::class)
            ->setPublic(true)
            ->setArguments([new Reference('enqueue.client.processor_registry')]);

        $container->register('enqueue.client.queue_consumer', QueueConsumer::class)
            ->setPublic(true)
            ->setArguments([
                new Reference('enqueue.transport.context'),
                new Reference('enqueue.consumption.extensions'),
            ]);

        // router
        $container->register('enqueue.client.router_processor', RouterProcessor::class)
            ->setPublic(true)
            ->setArguments([new Reference('enqueue.client.driver'), []]);
        $container->getDefinition('enqueue.client.processor_registry')
            ->addMethodCall('add', ['enqueue.client.router_processor', new Reference('enqueue.client.router_processor')]);
        $container->getDefinition('enqueue.client.meta.queue_meta_registry')
            ->addMethodCall('addProcessor', [$config['client']['router_queue'], 'enqueue.client.router_processor']);

        // extensions
        $extensions = [];
        if ($config['client']['redelivered_delay_time']) {
            $container->register('enqueue.client.delay_redelivered_message_extension', DelayRedeliveredMessageExtension::class)
                ->setPublic(true)
                ->setArguments([
                    new Reference('enqueue.client.driver'),
                    $config['client']['redelivered_delay_time'],
            ]);

            $extensions[] = new Reference('enqueue.client.delay_redelivered_message_extension');
        }

        $container->register('enqueue.client.extension.set_router_properties', SetRouterPropertiesExtension::class)
            ->setPublic(true)
            ->setArguments([new Reference('enqueue.client.driver')]);

        $extensions[] = new Reference('enqueue.client.extension.set_router_properties');

        $container->register('enqueue.consumption.extensions', ConsumptionChainExtension::class)
            ->setPublic(true)
            ->setArguments([$extensions]);
    }

    /**
     * @return NodeInterface
     */
    private function createConfiguration()
    {
        $tb = new TreeBuilder();
        $rootNode = $tb->root('enqueue');

        $transportChildren = $rootNode->children()
            ->arrayNode('transport')->isRequired()->children();

        foreach ($this->factories as $factory) {
            $factory->addConfiguration(
                $transportChildren->arrayNode($factory->getName())
            );
        }

        $rootNode->children()
            ->arrayNode('client')->children()
                ->scalarNode('prefix')->defaultValue('enqueue')->end()
                ->scalarNode('app_name')->defaultValue('app')->end()
                ->scalarNode('router_topic')->defaultValue(Config::DEFAULT_PROCESSOR_QUEUE_NAME)->cannotBeEmpty()->end()
                ->scalarNode('router_queue')->defaultValue(Config::DEFAULT_PROCESSOR_QUEUE_NAME)->cannotBeEmpty()->end()
                ->scalarNode('default_processor_queue')->defaultValue(Config::DEFAULT_PROCESSOR_QUEUE_NAME)->cannotBeEmpty()->end()
                ->integerNode('redelivered_delay_time')->min(0)->defaultValue(0)->end()
            ->end()->end()
            ->arrayNode('extensions')->addDefaultsIfNotSet()->children()
                ->booleanNode('signal_extension')->defaultValue(function_exists('pcntl_signal_dispatch'))->end()
            ->end()->end()
        ;

        return $tb->buildTree();
    }
}
