<?php

namespace Enqueue\Bundle\DependencyInjection;

use Enqueue\AsyncCommand\DependencyInjection\AsyncCommandExtension;
use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncEventDispatcherExtension;
use Enqueue\Bundle\Consumption\Extension\DoctrineClearIdentityMapExtension;
use Enqueue\Bundle\Consumption\Extension\DoctrineClosedEntityManagerExtension;
use Enqueue\Bundle\Consumption\Extension\DoctrinePingConnectionExtension;
use Enqueue\Bundle\Consumption\Extension\ResetServicesExtension;
use Enqueue\Bundle\Profiler\MessageQueueCollector;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\Extension\SignalExtension;
use Enqueue\JobQueue\Job;
use Enqueue\Monitoring\Symfony\DependencyInjection\MonitoringFactory;
use Enqueue\Symfony\Client\DependencyInjection\ClientFactory;
use Enqueue\Symfony\DependencyInjection\TransportFactory;
use Enqueue\Symfony\DiUtils;
use Interop\Queue\Context;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

final class EnqueueExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $config = $this->processConfiguration($this->getConfiguration($configs, $container), $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        // find default configuration
        $defaultName = null;
        foreach ($config as $name => $modules) {
            // set first as default
            if (null === $defaultName) {
                $defaultName = $name;
            }

            // or with name 'default'
            if (DiUtils::DEFAULT_CONFIG === $name) {
                $defaultName = $name;
            }
        }

        $transportNames = [];
        $clientNames = [];
        foreach ($config as $name => $modules) {
            // transport & consumption
            $transportNames[] = $name;

            $transportFactory = (new TransportFactory($name, $defaultName === $name));
            $transportFactory->buildConnectionFactory($container, $modules['transport']);
            $transportFactory->buildContext($container, []);
            $transportFactory->buildQueueConsumer($container, $modules['consumption']);
            $transportFactory->buildRpcClient($container, []);

            // client
            if (isset($modules['client'])) {
                $clientNames[] = $name;

                $clientConfig = $modules['client'];
                // todo
                $clientConfig['transport'] = $modules['transport'];
                $clientConfig['consumption'] = $modules['consumption'];

                $clientFactory = new ClientFactory($name, $defaultName === $name);
                $clientFactory->build($container, $clientConfig);
                $clientFactory->createDriver($container, $modules['transport']);
                $clientFactory->createFlushSpoolProducerListener($container);
            }

            // monitoring
            if (isset($modules['monitoring'])) {
                $monitoringFactory = new MonitoringFactory($name);
                $monitoringFactory->buildStorage($container, $modules['monitoring']);
                $monitoringFactory->buildConsumerExtension($container, $modules['monitoring']);

                if (isset($modules['client'])) {
                    $monitoringFactory->buildClientExtension($container, $modules['monitoring']);
                }
            }

            // job-queue
            if (false == empty($modules['job']['enabled'])) {
                if (false === isset($modules['client'])) {
                    throw new \LogicException('Client is required for job-queue.');
                }

                if ($name !== $defaultName) {
                    throw new \LogicException('Job-queue supports only default configuration.');
                }

                $loader->load('job.yml');
            }

            // async events
            if (false == empty($modules['async_events']['enabled'])) {
                if ($name !== $defaultName) {
                    throw new \LogicException('Async events supports only default configuration.');
                }

                $extension = new AsyncEventDispatcherExtension();
                $extension->load([[
                    'context_service' => Context::class,
                ]], $container);
            }
        }

        $defaultClient = null;
        if (in_array($defaultName, $clientNames, true)) {
            $defaultClient = $defaultName;
        }

        $container->setParameter('enqueue.transports', $transportNames);
        $container->setParameter('enqueue.clients', $clientNames);

        $container->setParameter('enqueue.default_transport', $defaultName);

        if ($defaultClient) {
            $container->setParameter('enqueue.default_client', $defaultClient);
        }

        if ($defaultClient) {
            $this->setupAutowiringForDefaultClientsProcessors($container, $defaultClient);
        }

        $this->loadMessageQueueCollector($config, $container);
        $this->loadAsyncCommands($config, $container);

        // extensions
        $this->loadDoctrinePingConnectionExtension($config, $container);
        $this->loadDoctrineClearIdentityMapExtension($config, $container);
        $this->loadDoctrineOdmClearIdentityMapExtension($config, $container);
        $this->loadDoctrineClosedEntityManagerExtension($config, $container);
        $this->loadResetServicesExtension($config, $container);
        $this->loadSignalExtension($config, $container);
        $this->loadReplyExtension($config, $container);
    }

    public function getConfiguration(array $config, ContainerBuilder $container): Configuration
    {
        $rc = new \ReflectionClass(Configuration::class);

        $container->addResource(new FileResource($rc->getFileName()));

        return new Configuration($container->getParameter('kernel.debug'));
    }

    public function prepend(ContainerBuilder $container): void
    {
        $this->registerJobQueueDoctrineEntityMapping($container);
    }

    private function registerJobQueueDoctrineEntityMapping(ContainerBuilder $container)
    {
        if (!class_exists(Job::class)) {
            return;
        }

        $bundles = $container->getParameter('kernel.bundles');

        if (!isset($bundles['DoctrineBundle'])) {
            return;
        }

        foreach ($container->getExtensionConfig('doctrine') as $config) {
            // do not register mappings if dbal not configured.
            if (!empty($config['dbal'])) {
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

    private function setupAutowiringForDefaultClientsProcessors(ContainerBuilder $container, string $defaultClient)
    {
        $container->registerForAutoconfiguration(TopicSubscriberInterface::class)
            ->setPublic(true)
            ->addTag('enqueue.topic_subscriber', ['client' => $defaultClient])
        ;

        $container->registerForAutoconfiguration(CommandSubscriberInterface::class)
            ->setPublic(true)
            ->addTag('enqueue.command_subscriber', ['client' => $defaultClient])
        ;
    }

    private function loadDoctrinePingConnectionExtension(array $config, ContainerBuilder $container): void
    {
        $configNames = [];
        foreach ($config as $name => $modules) {
            if ($modules['extensions']['doctrine_ping_connection_extension']) {
                $configNames[] = $name;
            }
        }

        if ([] === $configNames) {
            return;
        }

        $extension = $container->register('enqueue.consumption.doctrine_ping_connection_extension', DoctrinePingConnectionExtension::class)
            ->addArgument(new Reference('doctrine'))
        ;

        foreach ($configNames as $name) {
            $extension->addTag('enqueue.consumption_extension', ['client' => $name]);
            $extension->addTag('enqueue.transport.consumption_extension', ['transport' => $name]);
        }
    }

    private function loadDoctrineClearIdentityMapExtension(array $config, ContainerBuilder $container): void
    {
        $configNames = [];
        foreach ($config as $name => $modules) {
            if ($modules['extensions']['doctrine_clear_identity_map_extension']) {
                $configNames[] = $name;
            }
        }

        if ([] === $configNames) {
            return;
        }

        $extension = $container->register('enqueue.consumption.doctrine_clear_identity_map_extension', DoctrineClearIdentityMapExtension::class)
            ->addArgument(new Reference('doctrine'))
        ;

        foreach ($configNames as $name) {
            $extension->addTag('enqueue.consumption_extension', ['client' => $name]);
            $extension->addTag('enqueue.transport.consumption_extension', ['transport' => $name]);
        }
    }

    private function loadDoctrineOdmClearIdentityMapExtension(array $config, ContainerBuilder $container): void
    {
        $configNames = [];
        foreach ($config as $name => $modules) {
            if ($modules['extensions']['doctrine_odm_clear_identity_map_extension']) {
                $configNames[] = $name;
            }
        }

        if ([] === $configNames) {
            return;
        }

        $extension = $container->register('enqueue.consumption.doctrine_odm_clear_identity_map_extension', DoctrineClearIdentityMapExtension::class)
            ->addArgument(new Reference('doctrine_mongodb'))
        ;

        foreach ($configNames as $name) {
            $extension->addTag('enqueue.consumption_extension', ['client' => $name]);
            $extension->addTag('enqueue.transport.consumption_extension', ['transport' => $name]);
        }
    }

    private function loadDoctrineClosedEntityManagerExtension(array $config, ContainerBuilder $container)
    {
        $configNames = [];
        foreach ($config as $name => $modules) {
            if ($modules['extensions']['doctrine_closed_entity_manager_extension']) {
                $configNames[] = $name;
            }
        }

        if ([] === $configNames) {
            return;
        }

        $extension = $container->register('enqueue.consumption.doctrine_closed_entity_manager_extension', DoctrineClosedEntityManagerExtension::class)
            ->addArgument(new Reference('doctrine'));

        foreach ($configNames as $name) {
            $extension->addTag('enqueue.consumption_extension', ['client' => $name]);
            $extension->addTag('enqueue.transport.consumption_extension', ['transport' => $name]);
        }
    }

    private function loadResetServicesExtension(array $config, ContainerBuilder $container)
    {
        $configNames = [];
        foreach ($config as $name => $modules) {
            if ($modules['extensions']['reset_services_extension']) {
                $configNames[] = $name;
            }
        }

        if ([] === $configNames) {
            return;
        }

        $extension = $container->register('enqueue.consumption.reset_services_extension', ResetServicesExtension::class)
            ->addArgument(new Reference('services_resetter'));

        foreach ($configNames as $name) {
            $extension->addTag('enqueue.consumption_extension', ['client' => $name]);
            $extension->addTag('enqueue.transport.consumption_extension', ['transport' => $name]);
        }
    }

    private function loadSignalExtension(array $config, ContainerBuilder $container): void
    {
        $configNames = [];
        foreach ($config as $name => $modules) {
            if ($modules['extensions']['signal_extension']) {
                $configNames[] = $name;
            }
        }

        if ([] === $configNames) {
            return;
        }

        $extension = $container->register('enqueue.consumption.signal_extension', SignalExtension::class);

        foreach ($configNames as $name) {
            $extension->addTag('enqueue.consumption_extension', ['client' => $name]);
            $extension->addTag('enqueue.transport.consumption_extension', ['transport' => $name]);
        }
    }

    private function loadReplyExtension(array $config, ContainerBuilder $container): void
    {
        $configNames = [];
        foreach ($config as $name => $modules) {
            if ($modules['extensions']['reply_extension']) {
                $configNames[] = $name;
            }
        }

        if ([] === $configNames) {
            return;
        }

        $extension = $container->register('enqueue.consumption.reply_extension', ReplyExtension::class);

        foreach ($configNames as $name) {
            $extension->addTag('enqueue.consumption_extension', ['client' => $name]);
            $extension->addTag('enqueue.transport.consumption_extension', ['transport' => $name]);
        }
    }

    private function loadAsyncCommands(array $config, ContainerBuilder $container): void
    {
        $configs = [];
        foreach ($config as $name => $modules) {
            if (false === empty($modules['async_commands']['enabled'])) {
                $configs[] = [
                    'name' => $name,
                    'timeout' => $modules['async_commands']['timeout'],
                    'command_name' => $modules['async_commands']['command_name'],
                    'queue_name' => $modules['async_commands']['queue_name'],
                ];
            }
        }

        if (false == $configs) {
            return;
        }

        if (false == class_exists(AsyncCommandExtension::class)) {
            throw new \LogicException('The "enqueue/async-command" package has to be installed.');
        }

        $extension = new AsyncCommandExtension();
        $extension->load(['clients' => $configs], $container);
    }

    private function loadMessageQueueCollector(array $config, ContainerBuilder $container)
    {
        $configNames = [];
        foreach ($config as $name => $modules) {
            if (isset($modules['client'])) {
                $configNames[] = $name;
            }
        }

        if (false == $configNames) {
            return;
        }

        $service = $container->register('enqueue.profiler.message_queue_collector', MessageQueueCollector::class);
        $service->addTag('data_collector', [
            'template' => '@Enqueue/Profiler/panel.html.twig',
            'id' => 'enqueue.message_queue',
        ]);

        foreach ($configNames as $configName) {
            $service->addMethodCall('addProducer', [$configName, DiUtils::create('client', $configName)->reference('producer')]);
        }
    }
}
