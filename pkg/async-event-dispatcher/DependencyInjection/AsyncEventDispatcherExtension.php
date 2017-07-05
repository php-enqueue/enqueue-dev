<?php

namespace Enqueue\AsyncEventDispatcher\DependencyInjection;

use Enqueue\AsyncEventDispatcher\OldProxyEventDispatcher;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel;

class AsyncEventDispatcherExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        if (version_compare(Kernel::VERSION, '3.3', '<')) {
            $container->setDefinition('enqueue.events.async_processor', new Definition(OldProxyEventDispatcher::class, [
                new Reference('service_container'),
                new Reference('enqueue.events.registry'),
                new Reference('enqueue.events.event_dispatcher'),
            ]));
        }
    }
}
