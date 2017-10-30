<?php

namespace Enqueue\Bundle\DependencyInjection;

use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncEventDispatcherExtension;
use Enqueue\Client\Producer;
use Enqueue\Client\TraceableProducer;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\JobQueue\Job;
use Enqueue\Null\Symfony\NullTransportFactory;
use Enqueue\Symfony\DefaultTransportFactory;
use Enqueue\Symfony\DriverFactoryInterface;
use Enqueue\Symfony\TransportFactoryInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class EnqueueExtension extends Extension implements PrependExtensionInterface
{
    /**
     * @var TransportFactoryInterface[]
     */
    private $factories;

    public function __construct()
    {
        $this->factories = [];

        $this->addTransportFactory(new DefaultTransportFactory());
        $this->addTransportFactory(new NullTransportFactory());
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
        $config = $this->processConfiguration(new Configuration($this->factories), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        foreach ($config['transport'] as $name => $transportConfig) {
            $this->factories[$name]->createConnectionFactory($container, $transportConfig);
            $this->factories[$name]->createContext($container, $transportConfig);
        }

        if (isset($config['client'])) {
            $loader->load('client.yml');
            $loader->load('extensions/flush_spool_producer_extension.yml');
            $loader->load('extensions/exclusive_command_extension.yml');

            foreach ($config['transport'] as $name => $transportConfig) {
                if ($this->factories[$name] instanceof DriverFactoryInterface) {
                    $this->factories[$name]->createDriver($container, $transportConfig);
                }
            }

            if (isset($config['transport']['default']['alias']) && false == isset($config['transport'][$config['transport']['default']['alias']])) {
                throw new \LogicException(sprintf('Transport is not enabled: %s', $config['transport']['default']['alias']));
            }

            $configDef = $container->getDefinition('enqueue.client.config');
            $configDef->setArguments([
                $config['client']['prefix'],
                $config['client']['app_name'],
                $config['client']['router_topic'],
                $config['client']['router_queue'],
                $config['client']['default_processor_queue'],
                $config['client']['router_processor'],
                isset($config['transport']['default']['alias']) ? $config['transport'][$config['transport']['default']['alias']] : [],
            ]);

            $container->setParameter('enqueue.client.router_queue_name', $config['client']['router_queue']);
            $container->setParameter('enqueue.client.default_queue_name', $config['client']['default_processor_queue']);

            if (false == empty($config['client']['traceable_producer'])) {
                $container->register(TraceableProducer::class, TraceableProducer::class)
                    ->setDecoratedService(Producer::class)
                    ->addArgument(new Reference(sprintf('%s.inner', TraceableProducer::class)))
                ;
            }

            if ($config['client']['redelivered_delay_time']) {
                $loader->load('extensions/delay_redelivered_message_extension.yml');

                $container->getDefinition('enqueue.client.delay_redelivered_message_extension')
                    ->replaceArgument(1, $config['client']['redelivered_delay_time'])
                ;
            }
        }

        // configure queue consumer
        $container->getDefinition(QueueConsumer::class)
            ->replaceArgument(2, $config['consumption']['idle_timeout'])
            ->replaceArgument(3, $config['consumption']['receive_timeout'])
        ;

        if ($container->hasDefinition('enqueue.client.queue_consumer')) {
            $container->getDefinition('enqueue.client.queue_consumer')
                ->replaceArgument(2, $config['consumption']['idle_timeout'])
                ->replaceArgument(3, $config['consumption']['receive_timeout'])
            ;
        }

        if ($config['job']) {
            if (false == class_exists(Job::class)) {
                throw new \LogicException('Seems "enqueue/job-queue" is not installed. Please fix this issue.');
            }

            $loader->load('job.yml');
        }

        if ($config['async_events']['enabled']) {
            $extension = new AsyncEventDispatcherExtension();
            $extension->load([[
                'context_service' => 'enqueue.transport.default.context',
            ]], $container);
        }

        if ($config['extensions']['doctrine_ping_connection_extension']) {
            $loader->load('extensions/doctrine_ping_connection_extension.yml');
        }

        if ($config['extensions']['doctrine_clear_identity_map_extension']) {
            $loader->load('extensions/doctrine_clear_identity_map_extension.yml');
        }

        if ($config['extensions']['signal_extension']) {
            $loader->load('extensions/signal_extension.yml');
        }

        if ($config['extensions']['reply_extension']) {
            $loader->load('extensions/reply_extension.yml');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return Configuration
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        $rc = new \ReflectionClass(Configuration::class);

        $container->addResource(new FileResource($rc->getFileName()));

        return new Configuration($this->factories);
    }

    public function prepend(ContainerBuilder $container)
    {
        $this->registerJobQueueDoctrineEntityMapping($container);
    }

    private function registerJobQueueDoctrineEntityMapping(ContainerBuilder $container)
    {
        if (false == class_exists(Job::class)) {
            return;
        }

        $bundles = $container->getParameter('kernel.bundles');

        if (false == isset($bundles['DoctrineBundle'])) {
            return;
        }

        foreach ($container->getExtensionConfig('doctrine') as $config) {
            // do not register mappings if dbal not configured.
            if (false == empty($config['dbal'])) {
                $rc = new \ReflectionClass(Job::class);
                $jobQueueRootDir = dirname($rc->getFileName());
                $container->prependExtensionConfig('doctrine', [
                    'orm' => [
                        'mappings' => [
                            'enqueue_job_queue' => [
                                'is_bundle' => false,
                                'type' => 'xml',
                                'dir' => $jobQueueRootDir.'/Doctrine/mapping',
                                'prefix' => 'Enqueue\JobQueue\Doctrine\Entity',
                            ],
                        ],
                    ],
                ]);
                break;
            }
        }
    }
}
