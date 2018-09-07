<?php

namespace Enqueue\SimpleClient;

use Enqueue\AmqpBunny\AmqpConnectionFactory as AmqpBunnyConnectionFactory;
use Enqueue\AmqpExt\AmqpConnectionFactory as AmqpExtConnectionFactory;
use Enqueue\AmqpLib\AmqpConnectionFactory as AmqpLibConnectionFactory;
use Enqueue\Client\ArrayProcessorRegistry;
use Enqueue\Client\Config;
use Enqueue\Client\DelegateProcessor;
use Enqueue\Client\DriverInterface;
use Enqueue\Client\Meta\QueueMetaRegistry;
use Enqueue\Client\Meta\TopicMetaRegistry;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\RouterProcessor;
use Enqueue\Consumption\CallbackProcessor;
use Enqueue\Consumption\ExtensionInterface;
use Enqueue\Consumption\QueueConsumerInterface;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\Symfony\DbalTransportFactory;
use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\Symfony\FsTransportFactory;
use Enqueue\Gps\GpsConnectionFactory;
use Enqueue\Gps\Symfony\GpsTransportFactory;
use Enqueue\Mongodb\MongodbConnectionFactory;
use Enqueue\Mongodb\Symfony\MongodbTransportFactory;
use Enqueue\Null\Symfony\NullTransportFactory;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\RdKafka\Symfony\RdKafkaTransportFactory;
use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Redis\Symfony\RedisTransportFactory;
use Enqueue\Rpc\Promise;
use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\Symfony\SqsTransportFactory;
use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Stomp\Symfony\RabbitMqStompTransportFactory;
use Enqueue\Stomp\Symfony\StompTransportFactory;
use Enqueue\Symfony\AmqpTransportFactory;
use Enqueue\Symfony\DefaultTransportFactory;
use Enqueue\Symfony\MissingTransportFactory;
use Enqueue\Symfony\RabbitMqAmqpTransportFactory;
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
     * amqp:
     * amqp://guest:guest@localhost:5672/%2f?lazy=1&persisted=1
     * file://foo/bar/
     * null:
     *
     * or an array, the most simple:
     *
     *$config = [
     *   'transport' => [
     *     'default' => 'amqp',
     *     'amqp'          => [], // amqp options here
     *   ],
     * ]
     *
     * or a with all details:
     *
     * $config = [
     *   'transport' => [
     *     'default' => 'amqp',
     *     'amqp'          => [],
     *     ....
     *   ],
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
     * @param string                $topic
     * @param string                $processorName
     * @param callable|PsrProcessor $processor
     */
    public function bind($topic, $processorName, $processor)
    {
        if (is_callable($processor)) {
            $processor = new CallbackProcessor($processor);
        }

        if (false == $processor instanceof PsrProcessor) {
            throw new \LogicException('The processor must be either callable or instance of PsrProcessor');
        }

        $queueName = $this->getConfig()->getDefaultProcessorQueueName();

        $this->getTopicMetaRegistry()->addProcessor($topic, $processorName);
        $this->getQueueMetaRegistry()->addProcessor($queueName, $processorName);
        $this->getProcessorRegistry()->add($processorName, $processor);
        $this->getRouterProcessor()->add($topic, $queueName, $processorName);
    }

    /**
     * @param string $command
     * @param mixed  $message
     * @param bool   $needReply
     *
     * @return Promise|null
     */
    public function sendCommand($command, $message, $needReply = false)
    {
        return $this->getProducer()->sendCommand($command, $message, $needReply);
    }

    /**
     * @param string       $topic
     * @param string|array $message
     */
    public function sendEvent($topic, $message)
    {
        $this->getProducer()->sendEvent($topic, $message);
    }

    /**
     * @param ExtensionInterface|null $runtimeExtension
     */
    public function consume(ExtensionInterface $runtimeExtension = null)
    {
        $this->setupBroker();
        $processor = $this->getDelegateProcessor();
        $queueConsumer = $this->getQueueConsumer();

        $defaultQueueName = $this->getConfig()->getDefaultProcessorQueueName();
        $defaultTransportQueueName = $this->getConfig()->createTransportQueueName($defaultQueueName);

        $queueConsumer->bind($defaultTransportQueueName, $processor);
        if ($this->getConfig()->getRouterQueueName() != $defaultQueueName) {
            $routerTransportQueueName = $this->getConfig()->createTransportQueueName($this->getConfig()->getRouterQueueName());

            $queueConsumer->bind($routerTransportQueueName, $processor);
        }

        $queueConsumer->consume($runtimeExtension);
    }

    /**
     * @return PsrContext
     */
    public function getContext()
    {
        return $this->container->get('enqueue.transport.context');
    }

    public function getQueueConsumer(): QueueConsumerInterface
    {
        return $this->container->get('enqueue.client.queue_consumer');
    }

    /**
     * @return Config
     */
    public function getConfig()
    {
        return $this->container->get('enqueue.client.config');
    }

    /**
     * @return DriverInterface
     */
    public function getDriver()
    {
        return $this->container->get('enqueue.client.driver');
    }

    /**
     * @return TopicMetaRegistry
     */
    public function getTopicMetaRegistry()
    {
        return $this->container->get('enqueue.client.meta.topic_meta_registry');
    }

    /**
     * @return QueueMetaRegistry
     */
    public function getQueueMetaRegistry()
    {
        return $this->container->get('enqueue.client.meta.queue_meta_registry');
    }

    /**
     * @param bool $setupBroker
     *
     * @return ProducerInterface
     */
    public function getProducer($setupBroker = false)
    {
        $setupBroker && $this->setupBroker();

        return $this->container->get('enqueue.client.producer');
    }

    public function setupBroker()
    {
        $this->getDriver()->setupBroker();
    }

    /**
     * @return ArrayProcessorRegistry
     */
    public function getProcessorRegistry()
    {
        return $this->container->get('enqueue.client.processor_registry');
    }

    /**
     * @return DelegateProcessor
     */
    public function getDelegateProcessor()
    {
        return $this->container->get('enqueue.client.delegate_processor');
    }

    /**
     * @return RouterProcessor
     */
    public function getRouterProcessor()
    {
        return $this->container->get('enqueue.client.router_processor');
    }

    /**
     * @param array|string     $config
     * @param ContainerBuilder $container
     *
     * @return ContainerInterface
     */
    private function buildContainer($config, ContainerBuilder $container)
    {
        $config = $this->buildConfig($config);
        $extension = $this->buildContainerExtension();

        $container->registerExtension($extension);
        $container->loadFromExtension($extension->getAlias(), $config);

        $container->compile();

        return $container;
    }

    /**
     * @return SimpleClientContainerExtension
     */
    private function buildContainerExtension()
    {
        $extension = new SimpleClientContainerExtension();

        $extension->addTransportFactory(new DefaultTransportFactory('default'));
        $extension->addTransportFactory(new NullTransportFactory('null'));

        if (class_exists(StompConnectionFactory::class)) {
            $extension->addTransportFactory(new StompTransportFactory('stomp'));
            $extension->addTransportFactory(new RabbitMqStompTransportFactory('rabbitmq_stomp'));
        } else {
            $extension->addTransportFactory(new MissingTransportFactory('stomp', ['enqueue/stomp']));
            $extension->addTransportFactory(new MissingTransportFactory('rabbitmq_stomp', ['enqueue/stomp']));
        }

        if (
            class_exists(AmqpBunnyConnectionFactory::class) ||
            class_exists(AmqpExtConnectionFactory::class) ||
            class_exists(AmqpLibConnectionFactory::class)
        ) {
            $extension->addTransportFactory(new AmqpTransportFactory('amqp'));
            $extension->addTransportFactory(new RabbitMqAmqpTransportFactory('rabbitmq_amqp'));
        } else {
            $amppPackages = ['enqueue/amqp-ext', 'enqueue/amqp-bunny', 'enqueue/amqp-lib'];
            $extension->addTransportFactory(new MissingTransportFactory('amqp', $amppPackages));
            $extension->addTransportFactory(new MissingTransportFactory('rabbitmq_amqp', $amppPackages));
        }

        if (class_exists(FsConnectionFactory::class)) {
            $extension->addTransportFactory(new FsTransportFactory('fs'));
        } else {
            $extension->addTransportFactory(new MissingTransportFactory('fs', ['enqueue/fs']));
        }

        if (class_exists(RedisConnectionFactory::class)) {
            $extension->addTransportFactory(new RedisTransportFactory('redis'));
        } else {
            $extension->addTransportFactory(new MissingTransportFactory('redis', ['enqueue/redis']));
        }

        if (class_exists(DbalConnectionFactory::class)) {
            $extension->addTransportFactory(new DbalTransportFactory('dbal'));
        } else {
            $extension->addTransportFactory(new MissingTransportFactory('dbal', ['enqueue/dbal']));
        }

        if (class_exists(SqsConnectionFactory::class)) {
            $extension->addTransportFactory(new SqsTransportFactory('sqs'));
        } else {
            $extension->addTransportFactory(new MissingTransportFactory('sqs', ['enqueue/sqs']));
        }

        if (class_exists(GpsConnectionFactory::class)) {
            $extension->addTransportFactory(new GpsTransportFactory('gps'));
        } else {
            $extension->addTransportFactory(new MissingTransportFactory('gps', ['enqueue/gps']));
        }

        if (class_exists(RdKafkaConnectionFactory::class)) {
            $extension->addTransportFactory(new RdKafkaTransportFactory('rdkafka'));
        } else {
            $extension->addTransportFactory(new MissingTransportFactory('rdkafka', ['enqueue/rdkafka']));
        }

        if (class_exists(MongodbConnectionFactory::class)) {
            $extension->addTransportFactory(new MongodbTransportFactory('mongodb'));
        } else {
            $extension->addTransportFactory(new MissingTransportFactory('mongodb', ['enqueue/mongodb']));
        }

        return $extension;
    }

    /**
     * @param array|string $config
     *
     * @return array
     */
    private function buildConfig($config)
    {
        if (is_string($config) && false !== strpos($config, ':')) {
            $extConfig = [
                'client' => [],
                'transport' => [
                    'default' => $config,
                ],
            ];
        } elseif (is_string($config)) {
            $extConfig = [
                'client' => [],
                'transport' => [
                    'default' => $config,
                    $config => [],
                ],
            ];
        } elseif (is_array($config)) {
            $extConfig = array_replace_recursive([
                'client' => [],
                'transport' => [],
            ], $config);
        } else {
            throw new \LogicException('Expects config is string or array');
        }

        if (empty($extConfig['transport']['default'])) {
            $defaultTransport = null;
            foreach ($extConfig['transport'] as $transport => $config) {
                if ('default' === $transport) {
                    continue;
                }

                $defaultTransport = $transport;
                break;
            }

            if (false == $defaultTransport) {
                throw new \LogicException('There is no transport configured');
            }

            $extConfig['transport']['default'] = $defaultTransport;
        }

        return $extConfig;
    }
}
