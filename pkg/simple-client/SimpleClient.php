<?php

namespace Enqueue\SimpleClient;

use Enqueue\Client\ArrayProcessorRegistry;
use Enqueue\Client\Config;
use Enqueue\Client\DelegateProcessor;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Message;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Client\Meta\TopicMetaRegistry;
use Enqueue\Client\ProcessorRegistryInterface;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Client\RouterProcessor;
use Enqueue\Consumption\CallbackProcessor;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Rpc\Promise;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrProcessor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class SimpleClient
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array|string
     */
    private $config;

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
     * @param string|array          $config
     * @param ContainerBuilder|null $container
     */
    public function __construct($config, ContainerBuilder $container = null)
    {
        $this->container = $this->buildContainer($config, $container ?: new ContainerBuilder());
        $this->config = $config;
    }

    /**
     * @param callable|PsrProcessor $processor
     */
    public function bindTopic(string $topic, $processor, string $processorName = null): void
    {
        if (is_callable($processor)) {
            $processor = new CallbackProcessor($processor);
        }

        if (false == $processor instanceof PsrProcessor) {
            throw new \LogicException('The processor must be either callable or instance of PsrProcessor');
        }

        $processorName = $processorName ?: uniqid(get_class($processor));

        $this->getRouteCollection()->add(new Route($topic, Route::TOPIC, $processorName));
        $this->getProcessorRegistry()->add($processorName, $processor);
    }

    /**
     * @param callable|PsrProcessor $processor
     */
    public function bindCommand(string $command, $processor, string $processorName = null): void
    {
        if (is_callable($processor)) {
            $processor = new CallbackProcessor($processor);
        }

        if (false == $processor instanceof PsrProcessor) {
            throw new \LogicException('The processor must be either callable or instance of PsrProcessor');
        }

        $processorName = $processorName ?: uniqid(get_class($processor));

        $this->getRouteCollection()->add(new Route($command, Route::COMMAND, $processorName));
        $this->getProcessorRegistry()->add($processorName, $processor);
    }

    /**
     * @param string|array|\JsonSerializable|Message $message
     */
    public function sendCommand(string $command, $message, bool $needReply = false): ?Promise
    {
        return $this->getProducer()->sendCommand($command, $message, $needReply);
    }

    /**
     * @param string|array|Message $message
     */
    public function sendEvent(string $topic, $message): void
    {
        $this->getProducer()->sendEvent($topic, $message);
    }

    public function consume(ExtensionInterface $runtimeExtension = null): void
    {
        $this->setupBroker();
        $processor = $this->getDelegateProcessor();
        $queueConsumer = $this->getQueueConsumer();

        $defaultQueueName = $this->getConfig()->getDefaultProcessorQueueName();
        $defaultTransportQueueName = $this->getDriver()->createQueue($defaultQueueName);
        $queueConsumer->bind($defaultTransportQueueName, $processor);

        $routerQueueName = $this->getConfig()->getRouterQueueName();
        if ($routerQueueName != $defaultQueueName) {
            $routerTransportQueueName = $this->getDriver()->createQueue($routerQueueName);

            $queueConsumer->bind($routerTransportQueueName, $processor);
        }

        $queueConsumer->consume($runtimeExtension);
    }

    public function getContext(): PsrContext
    {
        return $this->container->get('enqueue.transport.context');
    }

    public function getQueueConsumer(): QueueConsumerInterface
    {
        return $this->container->get('enqueue.client.queue_consumer');
    }

    public function getConfig(): Config
    {
        return $this->container->get('enqueue.client.config');
    }

    public function getDriver(): DriverInterface
    {
        return $this->container->get('enqueue.client.default.driver');
    }

    public function getTopicMetaRegistry(): TopicMetaRegistry
    {
        return $this->container->get('enqueue.client.meta.topic_meta_registry');
    }

    public function getQueueMetaRegistry(): QueueMetaRegistry
    {
        return $this->container->get('enqueue.client.meta.queue_meta_registry');
    }

    public function getProducer(bool $setupBroker = false): ProducerInterface
    {
        $setupBroker && $this->setupBroker();

        return $this->container->get('enqueue.client.producer');
    }

    public function setupBroker(): void
    {
        $this->getDriver()->setupBroker();
    }

    /**
     * @return ArrayProcessorRegistry
     */
    public function getProcessorRegistry(): ProcessorRegistryInterface
    {
        return $this->container->get('enqueue.client.processor_registry');
    }

    public function getDelegateProcessor(): DelegateProcessor
    {
        return $this->container->get('enqueue.client.delegate_processor');
    }

    public function getRouterProcessor(): RouterProcessor
    {
        return $this->container->get('enqueue.client.router_processor');
    }

    private function getRouteCollection(): RouteCollection
    {
        return $this->container->get('enqueue.client.route_collection');
    }

    private function buildContainer($config, ContainerBuilder $container): ContainerInterface
    {
        $extension = new SimpleClientContainerExtension();
        $container->registerExtension($extension);
        $container->loadFromExtension($extension->getAlias(), $config);

        $container->compile();

        return $container;
    }
}
