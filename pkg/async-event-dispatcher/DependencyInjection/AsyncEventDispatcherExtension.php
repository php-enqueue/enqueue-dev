<?php

namespace Enqueue\AsyncEventDispatcher\DependencyInjection;

use Enqueue\AsyncEventDispatcher\AsyncProcessor;
use Enqueue\AsyncEventDispatcher\Commands;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;

class AsyncEventDispatcherExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $config = $this->processConfiguration(new Configuration(), $configs);

        $container->setAlias('enqueue.events.context', new Alias($config['context_service'], true));

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $container->register('enqueue.events.async_processor', AsyncProcessor::class)
            ->addArgument(new Reference('enqueue.events.registry'))
            ->addArgument(new Reference('enqueue.events.event_dispatcher'))
            ->addTag('enqueue.processor', [
                'command' => Commands::DISPATCH_ASYNC_EVENTS,
                'queue' => '%enqueue_events_queue%',
                'queue_prefixed' => false,
                'exclusive' => true,
            ])
            ->addTag('enqueue.transport.processor')
        ;
    }
}
