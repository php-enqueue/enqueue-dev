<?php

namespace Enqueue\Bundle\DependencyInjection;

use Enqueue\Client\Config;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * @var string[]
     */
    private $factoriesNames;

    /**
     * @param string[] $factoriesNames
     */
    public function __construct(array $factoriesNames)
    {
        $this->factoriesNames = $factoriesNames;
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();
        $rootNode = $tb->root('enqueue');

        $rootNode
            ->beforeNormalization()
            ->always(function ($v) {
                if (empty($v['transport'])) {
                    $v['transport'] = [
                        'default' => ['dsn' => 'null://'],
                    ];
                }

                if (is_string($v['transport'])) {
                    $v['transport'] = [
                        'default' => ['dsn' => $v['transport']],
                    ];
                }

                if (is_array($v['transport'])) {
                    foreach ($v['transport'] as $name => $config) {
                        if (empty($config)) {
                            $config = ['dsn' => 'null://'];
                        }

                        if (is_string($config)) {
                            $config = ['dsn' => $config];
                        }

                        if (empty($config['dsn']) && empty($config['config'])) {
                            throw new \LogicException(sprintf('The transport "%s" is incorrectly configured. Either "dsn" or "config" must be set.', $name));
                        }

                        $v['transport'][$name] = $config;
                    }
                }

                return $v;
            })
            ->end()
            ->children()
                ->arrayNode('transport')
                    ->prototype('array')
                        ->beforeNormalization()
                            ->ifString()->then(function ($v) {
                                return ['dsn' => $v];
                            })
                            ->ifEmpty()->then(function ($v) {
                                return ['dsn' => 'null://'];
                            })
                        ->end()
                        ->children()
                            ->scalarNode('dsn')->end()
                            ->enumNode('factory')->values($this->factoriesNames)->end()
                            ->variableNode('config')
                                ->treatNullLike([])
                                ->info('The place for factory specific options')
                            ->end()
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        $rootNode->children()
            ->arrayNode('client')->children()
                ->scalarNode('transport')->defaultValue('default')->end()
                ->booleanNode('traceable_producer')->defaultFalse()->end()
                ->scalarNode('prefix')->defaultValue('enqueue')->end()
                ->scalarNode('app_name')->defaultValue('app')->end()
                ->scalarNode('router_topic')->defaultValue('router')->cannotBeEmpty()->end()
                ->scalarNode('router_queue')->defaultValue(Config::DEFAULT_PROCESSOR_QUEUE_NAME)->cannotBeEmpty()->end()
                ->scalarNode('router_processor')->defaultValue('enqueue.client.router_processor')->end()
                ->scalarNode('default_processor_queue')->defaultValue(Config::DEFAULT_PROCESSOR_QUEUE_NAME)->cannotBeEmpty()->end()
                ->integerNode('redelivered_delay_time')->min(0)->defaultValue(0)->end()
            ->end()->end()
            ->booleanNode('job')->defaultFalse()->end()
            ->arrayNode('async_events')
                ->canBeEnabled()
                ->children()
                    ->booleanNode('spool_producer')->defaultFalse()->end()
                ->end()
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
