<?php

namespace Enqueue\Bundle\DependencyInjection;

use Enqueue\Monitoring\Symfony\DependencyInjection\MonitoringFactory;
use Enqueue\Symfony\Client\DependencyInjection\ClientFactory;
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
            ->always(function ($value) {
                if (empty($value)) {
                    return [
                        'default' => [
                            'transport' => [
                                'dsn' => 'null:',
                            ],
                        ],
                    ];
                }

                if (is_string($value)) {
                    return [
                        'default' => [
                            'transport' => [
                                'dsn' => $value,
                            ],
                        ],
                    ];
                }

                return $value;
            })
        ;

        $rootNode
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('key')
            ->arrayPrototype()
                ->children()
                    ->append(TransportFactory::getConfiguration())
                    ->append(TransportFactory::getQueueConsumerConfiguration())
                    ->append(ClientFactory::getConfiguration($this->debug))
                    ->append(MonitoringFactory::getConfiguration())
                    ->arrayNode('extensions')->addDefaultsIfNotSet()->children()
                        ->booleanNode('doctrine_ping_connection_extension')->defaultFalse()->end()
                        ->booleanNode('doctrine_clear_identity_map_extension')->defaultFalse()->end()
                        ->booleanNode('signal_extension')->defaultValue(function_exists('pcntl_signal_dispatch'))->end()
                        ->booleanNode('reply_extension')->defaultTrue()->end()
                    ->end()->end()
                    ->arrayNode('async_commands')
                        ->addDefaultsIfNotSet()
                        ->canBeEnabled()
                    ->end()
                ->end()
            ->end()
        ;

//        $rootNode->children()
//            ->booleanNode('job')->defaultFalse()->end()
//            ->arrayNode('async_events')
//                ->addDefaultsIfNotSet()
//                ->canBeEnabled()
//            ->end()
//        ;

        return $tb;
    }
}
