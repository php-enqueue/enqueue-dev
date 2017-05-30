<?php

namespace Enqueue\Bundle\DependencyInjection;

use Enqueue\Client\TraceableProducer;
use Enqueue\JobQueue\Job;
use Enqueue\Null\Symfony\NullTransportFactory;
use Enqueue\Psr\PsrConnectionFactory;
use Enqueue\Psr\PsrContext;
use Enqueue\Symfony\DefaultTransportFactory;
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

//        $this->factories[$name] = $transportFactory;
    }

    /**
     * @param string $name
     * @param string $factoryClass
     */
    public function addFactoryClass($name, $factoryClass)
    {
        if (array_key_exists($name, $this->factories)) {
            throw new \LogicException(sprintf('The factory with such name has already been added. Name "%s"', $name));
        }

        $this->factories[$name] = $factoryClass;
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(array_keys($this->factories)), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->getDefinition('enqueue.connection_factory_factory')
            ->replaceArgument(0, $this->factories);

        foreach ($config['transport'] as $name => $transportConfig) {
            $factoryId = sprintf('enqueue.transport.%s.connection_factory', $name);
            $contextId = sprintf('enqueue.transport.%s.context', $name);

            if (isset($transportConfig['dsn'])) {
                $transportConfig = $transportConfig['dsn'];
            }

            $container->register($factoryId, PsrConnectionFactory::class)
                ->addArgument($transportConfig)
                ->setFactory([new Reference('enqueue.connection_factory_factory'), 'createFactory'])
            ;

            $container->register($contextId, PsrContext::class)
                ->setFactory([new Reference($factoryId), 'createContext'])
            ;
        }

        if (isset($config['client'])) {
            $container->setAlias(
                'enqueue.client.transport.connection_factory',
                sprintf('enqueue.transport.%s.connection_factory', $config['client']['transport'])
            );
            $container->setAlias(
                'enqueue.client.transport.context',
                sprintf('enqueue.transport.%s.context', $config['client']['transport'])
            );

            $loader->load('client.yml');
            $loader->load('extensions/flush_spool_producer_extension.yml');

            foreach ($config['transport'] as $name => $transportConfig) {
                $this->factories[$name]->createDriver($container, $transportConfig);
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
                $producerId = 'enqueue.client.traceable_producer';
                $container->register($producerId, TraceableProducer::class)
                    ->setDecoratedService('enqueue.client.producer')
                    ->addArgument(new Reference('enqueue.client.traceable_producer.inner'))
                ;
            }

            if ($config['client']['redelivered_delay_time']) {
                $loader->load('extensions/delay_redelivered_message_extension.yml');

                $container->getDefinition('enqueue.client.delay_redelivered_message_extension')
                    ->replaceArgument(1, $config['client']['redelivered_delay_time'])
                ;
            }
        }

        if ($config['job']) {
            if (false == class_exists(Job::class)) {
                throw new \LogicException('Seems "enqueue/job-queue" is not installed. Please fix this issue.');
            }

            $loader->load('job.yml');
        }

        if (isset($config['async_events']['enabled'])) {
            $loader->load('events.yml');

            if (false == empty($config['async_events']['spool_producer'])) {
                $container->getDefinition('enqueue.events.async_listener')
                    ->replaceArgument(0, new Reference('enqueue.client.spool_producer'))
                ;
            }
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
