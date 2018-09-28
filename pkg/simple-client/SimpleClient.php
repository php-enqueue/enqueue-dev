<?php

namespace Enqueue\SimpleClient;

use Enqueue\Client\ArrayProcessorRegistry;
use Enqueue\Client\ChainExtension as ClientChainExtensions;
use Enqueue\Client\Config;
use Enqueue\Client\ConsumptionExtension\DelayRedeliveredMessageExtension;
use Enqueue\Client\ConsumptionExtension\SetRouterPropertiesExtension;
use Enqueue\Client\DelegateProcessor;
use Enqueue\Client\DriverFactory;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\Producer;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Client\RouterProcessor;
use Enqueue\ConnectionFactoryFactory;
use Enqueue\Consumption\CallbackProcessor;
use Enqueue\Consumption\ChainExtension as ConsumptionChainExtension;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Rpc\Promise;
use Enqueue\Rpc\RpcFactory;
use Enqueue\Symfony\DependencyInjection\TransportFactory;
use Interop\Queue\Processor;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\NodeInterface;
use Symfony\Component\Config\Definition\Processor as ConfigProcessor;

final class SimpleClient
{
    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var Producer
     */
    private $producer;

    /**
     * @var QueueConsumer
     */
    private $queueConsumer;

    /**
     * @var ArrayProcessorRegistry
     */
    private $processorRegistry;

    /**
     * @var DelegateProcessor
     */
    private $delegateProcessor;

    /**
     * The config could be a transport DSN (string) or an array, here's an example of a few DSNs:.
     *
     * $config = amqp:
     * $config = amqp://guest:guest@localhost:5672/%2f?lazy=1&persisted=1
     * $config = file://foo/bar/
     * $config = null:
     *
     * or an array:
     *
     * $config = [
     *   'transport' => [
     *      'dsn' => 'amqps://guest:guest@localhost:5672/%2f',
     *      'ssl_cacert' => '/a/dir/cacert.pem',
     *      'ssl_cert' => '/a/dir/cert.pem',
     *      'ssl_key' => '/a/dir/key.pem',
     * ]
     *
     * with custom connection factory class
     *
     * $config = [
     *   'transport' => [
     *      'dsn' => 'amqps://guest:guest@localhost:5672/%2f',
     *      'connection_factory_class' => 'aCustomConnectionFactory',
     *      // other options available options are factory_class and factory_service
     * ]
     *
     * The client config
     *
     * $config = [
     *   'transport' => 'null:',
     *   'client' => [
     *     'prefix'                   => 'enqueue',
     *     'app_name'                 => 'app',
     *     'router_topic'             => 'router',
     *     'router_queue'             => 'default',
     *     'default_processor_queue'  => 'default',
     *     'redelivered_delay_time'   => 0
     *   ],
     *   'extensions' => [
     *     'signal_extension' => true,
     *   ]
     * ]
     *
     *
     * @param string|array $config
     */
    public function __construct($config)
    {
        $this->build(['enqueue' => $config]);
    }

    /**
     * @param callable|Processor $processor
     */
    public function bindTopic(string $topic, $processor, string $processorName = null): void
    {
        if (is_callable($processor)) {
            $processor = new CallbackProcessor($processor);
        }

        if (false == $processor instanceof Processor) {
            throw new \LogicException('The processor must be either callable or instance of Processor');
        }

        $processorName = $processorName ?: uniqid(get_class($processor));

        $this->driver->getRouteCollection()->add(new Route($topic, Route::TOPIC, $processorName));
        $this->processorRegistry->add($processorName, $processor);
    }

    /**
     * @param callable|Processor $processor
     */
    public function bindCommand(string $command, $processor, string $processorName = null): void
    {
        if (is_callable($processor)) {
            $processor = new CallbackProcessor($processor);
        }

        if (false == $processor instanceof Processor) {
            throw new \LogicException('The processor must be either callable or instance of Processor');
        }

        $processorName = $processorName ?: uniqid(get_class($processor));

        $this->driver->getRouteCollection()->add(new Route($command, Route::COMMAND, $processorName));
        $this->processorRegistry->add($processorName, $processor);
    }

    /**
     * @param string|array|\JsonSerializable|Message $message
     */
    public function sendCommand(string $command, $message, bool $needReply = false): ?Promise
    {
        return $this->producer->sendCommand($command, $message, $needReply);
    }

    /**
     * @param string|array|Message $message
     */
    public function sendEvent(string $topic, $message): void
    {
        $this->producer->sendEvent($topic, $message);
    }

    public function consume(ExtensionInterface $runtimeExtension = null): void
    {
        $this->setupBroker();

        $boundQueues = [];

        $routerQueue = $this->getDriver()->createQueue($this->getDriver()->getConfig()->getRouterQueueName());
        $this->queueConsumer->bind($routerQueue, $this->delegateProcessor);
        $boundQueues[$routerQueue->getQueueName()] = true;

        foreach ($this->driver->getRouteCollection()->all() as $route) {
            $queue = $this->getDriver()->createRouteQueue($route);
            if (array_key_exists($queue->getQueueName(), $boundQueues)) {
                continue;
            }

            $this->queueConsumer->bind($queue, $this->delegateProcessor);

            $boundQueues[$queue->getQueueName()] = true;
        }

        $this->queueConsumer->consume($runtimeExtension);
    }

    public function getQueueConsumer(): QueueConsumerInterface
    {
        return $this->queueConsumer;
    }

    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    public function getProducer(bool $setupBroker = false): ProducerInterface
    {
        $setupBroker && $this->setupBroker();

        return $this->producer;
    }

    public function setupBroker(): void
    {
        $this->getDriver()->setupBroker();
    }

    public function build(array $configs): void
    {
        $configProcessor = new ConfigProcessor();
        $simpleClientConfig = $configProcessor->process($this->createConfiguration(), $configs);

        if (isset($simpleClientConfig['transport']['factory_service'])) {
            throw new \LogicException('transport.factory_service option is not supported by simple client');
        }
        if (isset($simpleClientConfig['transport']['factory_class'])) {
            throw new \LogicException('transport.factory_class option is not supported by simple client');
        }
        if (isset($simpleClientConfig['transport']['connection_factory_class'])) {
            throw new \LogicException('transport.connection_factory_class option is not supported by simple client');
        }

        $connectionFactoryFactory = new ConnectionFactoryFactory();
        $connection = $connectionFactoryFactory->create($simpleClientConfig['transport']);

        $clientExtensions = new ClientChainExtensions([]);

        $config = new Config(
            $simpleClientConfig['client']['prefix'],
            $simpleClientConfig['client']['app_name'],
            $simpleClientConfig['client']['router_topic'],
            $simpleClientConfig['client']['router_queue'],
            $simpleClientConfig['client']['default_processor_queue'],
            'enqueue.client.router_processor',
            $simpleClientConfig['transport']
        );
        $routeCollection = new RouteCollection([]);
        $driverFactory = new DriverFactory($config, $routeCollection);

        $driver = $driverFactory->create(
            $connection,
            $simpleClientConfig['transport']['dsn'],
            $simpleClientConfig['transport']
        );

        $rpcFactory = new RpcFactory($driver->getContext());

        $producer = new Producer($driver, $rpcFactory, $clientExtensions);

        $processorRegistry = new ArrayProcessorRegistry([]);

        $delegateProcessor = new DelegateProcessor($processorRegistry);

        // consumption extensions
        $consumptionExtensions = [];
        if ($simpleClientConfig['client']['redelivered_delay_time']) {
            $consumptionExtensions[] = new DelayRedeliveredMessageExtension($driver, $simpleClientConfig['client']['redelivered_delay_time']);
        }

        $consumptionExtensions[] = new SetRouterPropertiesExtension($driver);

        $consumptionChainExtension = new ConsumptionChainExtension($consumptionExtensions);
        $queueConsumer = new QueueConsumer($driver->getContext(), $consumptionChainExtension);

        $routerProcessor = new RouterProcessor($driver);

        $processorRegistry->add($config->getRouterProcessorName(), $routerProcessor);

        $this->driver = $driver;
        $this->producer = $producer;
        $this->queueConsumer = $queueConsumer;
        $this->delegateProcessor = $delegateProcessor;
        $this->processorRegistry = $processorRegistry;
    }

    private function createConfiguration(): NodeInterface
    {
        $tb = new TreeBuilder();
        $rootNode = $tb->root('enqueue');

        $rootNode
            ->beforeNormalization()
            ->ifEmpty()->then(function () {
                return ['transport' => ['dsn' => 'null:']];
            });

        $transportNode = $rootNode->children()->arrayNode('transport');
        (new TransportFactory('default'))->addConfiguration($transportNode);

        $rootNode->children()
            ->arrayNode('client')
            ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('prefix')->defaultValue('enqueue')->end()
                    ->scalarNode('app_name')->defaultValue('app')->end()
                    ->scalarNode('router_topic')->defaultValue(Config::DEFAULT_PROCESSOR_QUEUE_NAME)->cannotBeEmpty()->end()
                    ->scalarNode('router_queue')->defaultValue(Config::DEFAULT_PROCESSOR_QUEUE_NAME)->cannotBeEmpty()->end()
                    ->scalarNode('default_processor_queue')->defaultValue(Config::DEFAULT_PROCESSOR_QUEUE_NAME)->cannotBeEmpty()->end()
                    ->integerNode('redelivered_delay_time')->min(0)->defaultValue(0)->end()
                ->end()
            ->end()
                ->arrayNode('extensions')->addDefaultsIfNotSet()->children()
                ->booleanNode('signal_extension')->defaultValue(function_exists('pcntl_signal_dispatch'))->end()
            ->end()->end()
        ;

        return $tb->buildTree();
    }
}
