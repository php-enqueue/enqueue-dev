<?php

namespace Enqueue\Client;

use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpExt\Client\AmqpDriver;
use Enqueue\Client\ConsumptionExtension\SetRouterPropertiesExtension;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Client\Meta\TopicMetaRegistry;
use Enqueue\Consumption\CallbackProcessor;
use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\QueueConsumer;

/**
 * Experimental class. Use it speedup setup process and learning but consider to switch to custom soltion (build your own client).
 */
final class SimpleClient
{
    /**
     * @var AmqpContext
     */
    private $context;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var ArrayProcessorRegistry
     */
    private $processorsRegistry;

    /**
     * @var TopicMetaRegistry
     */
    private $topicsMetaRegistry;

    /**
     * @var RouterProcessor
     */
    private $routerProcessor;

    /**
     * @param AmqpContext $context
     * @param Config|null $config
     */
    public function __construct(AmqpContext $context, Config $config = null)
    {
        $this->context = $context;
        $this->config = $config ?: Config::create();

        $this->queueMetaRegistry = new QueueMetaRegistry($this->config, []);
        $this->queueMetaRegistry->add($this->config->getDefaultProcessorQueueName());
        $this->queueMetaRegistry->add($this->config->getRouterQueueName());

        $this->topicsMetaRegistry = new TopicMetaRegistry([]);
        $this->processorsRegistry = new ArrayProcessorRegistry();

        $this->driver = new AmqpDriver($context, $this->config, $this->queueMetaRegistry);
        $this->routerProcessor = new RouterProcessor($this->driver, []);

        $this->processorsRegistry->add($this->config->getRouterProcessorName(), $this->routerProcessor);
        $this->queueMetaRegistry->addProcessor($this->config->getRouterQueueName(), $this->routerProcessor);
    }

    /**
     * @param string $topic
     * @param callback
     */
    public function bind($topic, callable $processor)
    {
        $processorName = uniqid('', true);
        $queueName = $this->config->getDefaultProcessorQueueName();

        $this->topicsMetaRegistry->addProcessor($topic, $processorName);
        $this->queueMetaRegistry->addProcessor($queueName, $processorName);
        $this->processorsRegistry->add($processorName, new CallbackProcessor($processor));

        $this->routerProcessor->add($topic, $queueName, $processorName);
    }

    public function send($topic, $message)
    {
        $this->getProducer()->send($topic, $message);
    }

    public function consume(ExtensionInterface $runtimeExtension = null)
    {
        $this->driver->setupBroker();

        $processor = $this->getProcessor();

        $queueConsumer = new QueueConsumer($this->context, new ChainExtension([
            new SetRouterPropertiesExtension($this->driver),
        ]));

        $defaultQueueName = $this->config->getDefaultProcessorQueueName();
        $defaultTransportQueueName = $this->config->createTransportQueueName($defaultQueueName);

        $queueConsumer->bind($defaultTransportQueueName, $processor);
        if ($this->config->getRouterQueueName() != $defaultQueueName) {
            $routerTransportQueueName = $this->config->createTransportQueueName($this->config->getRouterQueueName());

            $queueConsumer->bind($routerTransportQueueName, $processor);
        }

        $queueConsumer->consume($runtimeExtension);
    }

    /**
     * @return MessageProducerInterface
     */
    private function getProducer()
    {
        $this->driver->setupBroker();

        return new MessageProducer($this->driver);
    }

    /**
     * @return DelegateProcessor
     */
    private function getProcessor()
    {
        return new DelegateProcessor($this->processorsRegistry);
    }
}
