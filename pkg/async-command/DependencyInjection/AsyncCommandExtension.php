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
            // BC compatibility
            if (!is_array($client)) {
                $client = [
                    'name' => $client,
                    'prefix' => '',
                    'timeout' => 60,
                ];
            }

            $id = sprintf('enqueue.async_command.%s.run_command_processor', $client['name']);
            $container->register($id, RunCommandProcessor::class)
                ->addArgument('%kernel.project_dir%', $client['timeout'])
                ->addTag('enqueue.processor', [
                    'client' => $client['name'],
                    'command' => $client['prefix'].Commands::RUN_COMMAND,
                    'queue' => $client['prefix'].Commands::RUN_COMMAND,
                    'prefix_queue' => false,
                    'exclusive' => true,
                ])
                ->addTag('enqueue.transport.processor')
            ;
        }
    }
}
