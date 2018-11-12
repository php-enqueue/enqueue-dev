<?php

namespace Enqueue\AsyncCommand\DependencyInjection;

use Enqueue\AsyncCommand\RunCommandProcessor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class AsyncCommandExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        $service = $container->register('enqueue.async_command.run_command_processor', RunCommandProcessor::class)
            ->addArgument('%kernel.project_dir%')
        ;

        foreach ($configs['clients'] as $client) {
            $service->addTag('enqueue.command_subscriber', ['client' => $client]);
        }
    }
}
