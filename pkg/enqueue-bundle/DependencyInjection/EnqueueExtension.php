<?php

namespace Enqueue\Bundle\DependencyInjection;

use Enqueue\AsyncCommand\DependencyInjection\AsyncCommandExtension;
use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncEventDispatcherExtension;
use Enqueue\Bundle\Consumption\Extension\DoctrineClearIdentityMapExtension;
use Enqueue\Bundle\Consumption\Extension\DoctrinePingConnectionExtension;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Consumption\Extension\ReplyExtension;
use Enqueue\Consumption\Extension\SignalExtension;
use Enqueue\JobQueue\Job;
use Enqueue\Monitoring\Symfony\DependencyInjection\MonitoringFactory;
use Enqueue\Symfony\Client\DependencyInjection\ClientFactory;
use Enqueue\Symfony\DependencyInjection\TransportFactory;
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
        foreach ($config as $name => $configs) {
            // set first as default
            if (null === $defaultName) {
                $defaultName = $name;
            }

            // or with name 'default'
            if ('default' === $name) {
                $defaultName = $name;
            }
        }

        $transportNames = [];
        $clientNames = [];
        foreach ($config as $name => $configs) {
            // transport & consumption
            $transportNames[] = $name;

            $transportFactory = (new TransportFactory($name));
            $transportFactory->buildConnectionFactory($container, $configs['transport']);
            $transportFactory->buildContext($container, []);
            $transportFactory->buildQueueConsumer($container, $configs['consumption']);
            $transportFactory->buildRpcClient($container, []);

            // client
            if (isset($configs['client'])) {
                $clientNames[] = $name;

                $clientConfig = $configs['client'];
                // todo
                $clientConfig['transport'] = $configs['transport'];
                $clientConfig['consumption'] = $configs['consumption'];

                $clientFactory = new ClientFactory($name);
                $clientFactory->build($container, $clientConfig, $defaultName === $name);
                $clientFactory->createDriver($container, $configs['transport']);
                $clientFactory->createFlushSpoolProducerListener($container);
            }

            // monitoring
            if (isset($configs['monitoring'])) {
                $monitoringFactory = new MonitoringFactory($name);
                $monitoringFactory->buildStorage($container, $configs['monitoring']);
                $monitoringFactory->buildConsumerExtension($container, $configs['monitoring']);

                if (isset($configs['client'])) {
                    $monitoringFactory->buildClientExtension($container, $configs['monitoring']);
                }
            }
        }

        $container->setParameter('enqueue.transports', $transportNames);
        $container->setParameter('enqueue.clients', $clientNames);

        $this->setupAutowiringForProcessors($config, $container);
        $this->loadAsyncCommands($config, $container);

        // extensions
        $this->loadDoctrinePingConnectionExtension($config, $container);
        $this->loadDoctrineClearIdentityMapExtension($config, $container);
        $this->loadSignalExtension($config, $container);
        $this->loadReplyExtension($config, $container);

        // @todo register MessageQueueCollector

//        if ($config['job']) {
//            if (!class_exists(Job::class)) {
//                throw new \LogicException('Seems "enqueue/job-queue" is not installed. Please fix this issue.');
//            }
//
//            $loader->load('job.yml');
//        }
//
//        if ($config['async_events']['enabled']) {
//            if (false == class_exists(AsyncEventDispatcherExtension::class)) {
//                throw new \LogicException('The "enqueue/async-event-dispatcher" package has to be installed.');
//            }
//
//            $extension = new AsyncEventDispatcherExtension();
//            $extension->load([[
//                'context_service' => 'enqueue.transport.default.context',
//            ]], $container);
//        }
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

    private function setupAutowiringForProcessors(array $config, ContainerBuilder $container)
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

        $topicSubscriber = $container->registerForAutoconfiguration(TopicSubscriberInterface::class)
            ->setPublic(true)
        ;

        $commandSubscriber = $container->registerForAutoconfiguration(CommandSubscriberInterface::class)
            ->setPublic(true)
        ;

        foreach ($configNames as $configName) {
            $topicSubscriber->addTag('enqueue.topic_subscriber', ['client' => $configName]);
            $commandSubscriber->addTag('enqueue.command_subscriber', ['client' => $configName]);
        }
    }

    private function loadDoctrinePingConnectionExtension(array $config, ContainerBuilder $container): void
    {
        $configNames = [];
        foreach ($config as $name => $modules) {
            if ($modules['extensions']['doctrine_ping_connection_extension']) {
                $configNames[] = $name;
            }
        }

        if (false == $configNames) {
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

        if (false == $configNames) {
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

    private function loadSignalExtension(array $config, ContainerBuilder $container): void
    {
        $configNames = [];
        foreach ($config as $name => $modules) {
            if ($modules['extensions']['signal_extension']) {
                $configNames[] = $name;
            }
        }

        if (false == $configNames) {
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

        if (false == $configNames) {
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
        $configNames = [];
        foreach ($config as $name => $modules) {
            if ($modules['async_commands']['enabled']) {
                $configNames[] = $name;
            }
        }

        if (false == $configNames) {
            return;
        }

        if (false == class_exists(AsyncCommandExtension::class)) {
            throw new \LogicException('The "enqueue/async-command" package has to be installed.');
        }

        $extension = new AsyncCommandExtension();
        $extension->load(['clients' => $configNames], $container);
    }
}
