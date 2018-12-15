<?php

namespace Enqueue\AsyncCommand\DependencyInjection;

use Enqueue\AsyncCommand\Commands;
use Enqueue\AsyncCommand\RunCommandProcessor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;

class AsyncCommandExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        foreach ($configs['clients'] as $client) {
            $id = sprintf('enqueue.async_command.%s.run_command_processor', $client);
            $container->register($id, RunCommandProcessor::class)
                ->addArgument('%kernel.project_dir%')
                ->addTag('enqueue.processor', [
                    'client' => $client,
                    'command' => Commands::RUN_COMMAND,
                    'queue' => Commands::RUN_COMMAND,
                    'queue_prefixed' => false,
                    'exclusive' => true,
                ])
                ->addTag('enqueue.transport.processor')
            ;
        }
    }
}
