<?php

namespace Enqueue\Bundle\DependencyInjection;

use Enqueue\AsyncCommand\DependencyInjection\AsyncCommandExtension;
use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncEventDispatcherExtension;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\JobQueue\Job;
use Enqueue\Symfony\Client\DependencyInjection\ClientFactory;
use Enqueue\Symfony\DependencyInjection\TransportFactory;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
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
            $transportFactory->buildConnectionFactory($container, $config['transport']);
            $transportFactory->buildContext($container, []);
            $transportFactory->buildQueueConsumer($container, $config['consumption']);
            $transportFactory->buildRpcClient($container, []);

            // client
            if (isset($configs['client'])) {
                $clientNames[] = $name;

                $clientConfig = $config['client'];
                // todo
                $clientConfig['transport'] = $config['transport'];
                $clientConfig['consumption'] = $config['consumption'];

                $clientFactory = new ClientFactory($name);
                $clientFactory->build($container, $clientConfig, $defaultName === $name);
                $clientFactory->createDriver($container, $config['transport']);
                $clientFactory->createFlushSpoolProducerListener($container);
            }
        }

        $container->setParameter('enqueue.transports', $transportNames);
        $container->setParameter('enqueue.clients', $clientNames);

        if ($clientNames) {
            $this->setupAutowiringForProcessors($container, $clientNames);
        }

        // @todo register MessageQueueCollector

        return;

        //
        if ($config['job']) {
            if (!class_exists(Job::class)) {
                throw new \LogicException('Seems "enqueue/job-queue" is not installed. Please fix this issue.');
            }

            $loader->load('job.yml');
        }

        if ($config['async_events']['enabled']) {
            if (false == class_exists(AsyncEventDispatcherExtension::class)) {
                throw new \LogicException('The "enqueue/async-event-dispatcher" package has to be installed.');
            }

            $extension = new AsyncEventDispatcherExtension();
            $extension->load([[
                'context_service' => 'enqueue.transport.default.context',
            ]], $container);
        }

        if ($config['async_commands']['enabled']) {
            if (false == class_exists(AsyncCommandExtension::class)) {
                throw new \LogicException('The "enqueue/async-command" package has to be installed.');
            }

            $extension = new AsyncCommandExtension();
            $extension->load([[]], $container);
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

    private function setupAutowiringForProcessors(ContainerBuilder $container, array $clientNames)
    {
        $topicSubscriber = $container->registerForAutoconfiguration(TopicSubscriberInterface::class)
            ->setPublic(true)
        ;

        $commandSubscriber = $container->registerForAutoconfiguration(CommandSubscriberInterface::class)
            ->setPublic(true)
        ;

        foreach ($clientNames as $clientName) {
            $topicSubscriber->addTag('enqueue.topic_subscriber', ['client' => $clientName]);
            $commandSubscriber->addTag('enqueue.command_subscriber', ['client' => $clientName]);
        }
    }
}
