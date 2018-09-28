<?php

namespace Enqueue\Bundle\DependencyInjection;

use Enqueue\Client\Config;
use Enqueue\Client\RouterProcessor;
use Enqueue\Symfony\DependencyInjection\TransportFactory;
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
        $tb = new TreeBuilder();
        $rootNode = $tb->root('enqueue');
        $rootNode
            ->beforeNormalization()
            ->ifEmpty()->then(function () {
                return ['transport' => ['dsn' => 'null:']];
            });

        $transportNode = $rootNode->children()->arrayNode('transport');
        (new TransportFactory('default'))->addConfiguration($transportNode);

        $rootNode->children()
            ->arrayNode('client')->children()
                ->booleanNode('traceable_producer')->defaultValue($this->debug)->end()
                ->scalarNode('prefix')->defaultValue('enqueue')->end()
                ->scalarNode('app_name')->defaultValue('app')->end()
                ->scalarNode('router_topic')->defaultValue('default')->cannotBeEmpty()->end()
                ->scalarNode('router_queue')->defaultValue('default')->cannotBeEmpty()->end()
                ->scalarNode('router_processor')->defaultValue(RouterProcessor::class)->end()
                ->scalarNode('default_processor_queue')->defaultValue('default')->cannotBeEmpty()->end()
                ->integerNode('redelivered_delay_time')->min(0)->defaultValue(0)->end()
            ->end()->end()
            ->arrayNode('consumption')->addDefaultsIfNotSet()->children()
                ->integerNode('idle_timeout')
                    ->min(0)
                    ->defaultValue(0)
                    ->info('the time in milliseconds queue consumer waits if no message received')
                ->end()
                ->integerNode('receive_timeout')
                    ->min(0)
                    ->defaultValue(100)
                    ->info('the time in milliseconds queue consumer waits for a message (100 ms by default)')
                ->end()
            ->end()->end()
            ->booleanNode('job')->defaultFalse()->end()
            ->arrayNode('async_events')
                ->addDefaultsIfNotSet()
                ->canBeEnabled()
            ->end()
            ->arrayNode('async_commands')
                ->addDefaultsIfNotSet()
                ->canBeEnabled()
            ->end()
            ->arrayNode('extensions')->addDefaultsIfNotSet()->children()
                ->booleanNode('doctrine_ping_connection_extension')->defaultFalse()->end()
                ->booleanNode('doctrine_clear_identity_map_extension')->defaultFalse()->end()
                ->booleanNode('signal_extension')->defaultValue(function_exists('pcntl_signal_dispatch'))->end()
                ->booleanNode('reply_extension')->defaultTrue()->end()
            ->end()->end()
        ;

        return $tb;
    }
}
