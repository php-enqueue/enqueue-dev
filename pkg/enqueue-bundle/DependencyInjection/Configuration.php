<?php

namespace Enqueue\Bundle\DependencyInjection;

use Enqueue\AsyncCommand\RunCommandProcessor;
use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncEventDispatcherExtension;
use Enqueue\JobQueue\Job;
use Enqueue\Monitoring\Symfony\DependencyInjection\MonitoringFactory;
use Enqueue\Symfony\Client\DependencyInjection\ClientFactory;
use Enqueue\Symfony\DependencyInjection\TransportFactory;
use Enqueue\Symfony\MissingComponentFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    private $debug;

    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    public function getConfigTreeBuilder(): TreeBuilder
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $tb = new TreeBuilder('enqueue');
            $rootNode = $tb->getRootNode();
        } else {
            $tb = new TreeBuilder();
            $rootNode = $tb->root('enqueue');
        }

        $rootNode
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('key')
            ->arrayPrototype()
                ->children()
                    ->append(TransportFactory::getConfiguration())
                    ->append(TransportFactory::getQueueConsumerConfiguration())
                    ->append(ClientFactory::getConfiguration($this->debug))
                    ->append($this->getMonitoringConfiguration())
                    ->append($this->getAsyncCommandsConfiguration())
                    ->append($this->getJobConfiguration())
                    ->append($this->getAsyncEventsConfiguration())
                    ->arrayNode('extensions')->addDefaultsIfNotSet()->children()
                        ->booleanNode('doctrine_ping_connection_extension')->defaultFalse()->end()
                        ->booleanNode('doctrine_clear_identity_map_extension')->defaultFalse()->end()
                        ->booleanNode('doctrine_odm_clear_identity_map_extension')->defaultFalse()->end()
                        ->booleanNode('doctrine_closed_entity_manager_extension')->defaultFalse()->end()
                        ->booleanNode('reset_services_extension')->defaultFalse()->end()
                        ->booleanNode('signal_extension')->defaultValue(function_exists('pcntl_signal_dispatch'))->end()
                        ->booleanNode('reply_extension')->defaultTrue()->end()
                    ->end()->end()
                ->end()
            ->end()
        ;

        return $tb;
    }

    private function getMonitoringConfiguration(): ArrayNodeDefinition
    {
        if (false === class_exists(MonitoringFactory::class)) {
            return MissingComponentFactory::getConfiguration('monitoring', ['enqueue/monitoring']);
        }

        return MonitoringFactory::getConfiguration();
    }

    private function getAsyncCommandsConfiguration(): ArrayNodeDefinition
    {
        if (false === class_exists(RunCommandProcessor::class)) {
            return MissingComponentFactory::getConfiguration('async_commands', ['enqueue/async-command']);
        }

        return (new ArrayNodeDefinition('async_commands'))
            ->children()
                ->booleanNode('enabled')->defaultFalse()->end()
                ->integerNode('timeout')->min(0)->defaultValue(60)->end()
                ->scalarNode('command_name')->defaultNull()->end()
                ->scalarNode('queue_name')->defaultNull()->end()
            ->end()
            ->addDefaultsIfNotSet()
            ->canBeEnabled()
        ;
    }

    private function getJobConfiguration(): ArrayNodeDefinition
    {
        if (false === class_exists(Job::class)) {
            return MissingComponentFactory::getConfiguration('job', ['enqueue/job-queue']);
        }

        return (new ArrayNodeDefinition('job'))
            ->children()
                ->booleanNode('default_mapping')
                    ->defaultTrue()
                    ->info('Adds bundle\'s default Job entity mapping to application\'s entity manager')
                ->end()
            ->end()
            ->addDefaultsIfNotSet()
            ->canBeEnabled()
        ;
    }

    private function getAsyncEventsConfiguration(): ArrayNodeDefinition
    {
        if (false == class_exists(AsyncEventDispatcherExtension::class)) {
            return MissingComponentFactory::getConfiguration('async_events', ['enqueue/async-event-dispatcher']);
        }

        return (new ArrayNodeDefinition('async_events'))
            ->addDefaultsIfNotSet()
            ->canBeEnabled()
        ;
    }
}
