<?php

namespace Enqueue\Bundle\DependencyInjection;

use Enqueue\Monitoring\Symfony\DependencyInjection\MonitoringFactory;
use Enqueue\Symfony\Client\DependencyInjection\ClientFactory;
use Enqueue\Symfony\DependencyInjection\TransportFactory;
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

        $transportFactory = new TransportFactory('default');

        $transportConfig = $transportFactory->getConfiguration('transport');
        $transportConfig->isRequired();

        $consumerConfig = $transportFactory->getQueueConsumerConfiguration('consumption');

        $clientConfig = (new ClientFactory('default'))->getConfiguration('client', $this->debug);

        $monitoringConfig = (new MonitoringFactory('default'))->getConfiguration('monitoring');

        $rootNode
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('key')
            ->arrayPrototype()
                ->children()
                    ->append($transportConfig)
                    ->append($consumerConfig)
                    ->append($clientConfig)
                    ->append($monitoringConfig)
                ->end()
            ->end()
        ;


//        $transportFactory = new TransportFactory('default');
//
//        /** @var ArrayNodeDefinition $transportNode */
//        $transportNode = $rootNode->children()->arrayNode('transport');
//        $transportNode
//            ->beforeNormalization()
//            ->always(function ($value) {
//                if (empty($value)) {
//                    return ['default' => ['dsn' => 'null:']];
//                }
//                if (is_string($value)) {
//                    return ['default' => ['dsn' => $value]];
//                }
//
//                if (is_array($value) && array_key_exists('dsn', $value)) {
//                    return ['default' => $value];
//                }
//
//                return $value;
//            });
//        $transportPrototypeNode = $transportNode
//            ->requiresAtLeastOneElement()
//            ->useAttributeAsKey('key')
//            ->prototype('array')
//        ;
//
//        $transportFactory->addTransportConfiguration($transportPrototypeNode);
//
//        $consumptionNode = $rootNode->children()->arrayNode('consumption');
//        $transportFactory->addQueueConsumerConfiguration($consumptionNode);
//
//        $clientFactory = new ClientFactory('default');
//        $clientNode = $rootNode->children()->arrayNode('client');
//        $clientFactory->addClientConfiguration($clientNode, $this->debug);
//
//        $rootNode->children()
//            ->booleanNode('job')->defaultFalse()->end()
//            ->arrayNode('async_events')
//                ->addDefaultsIfNotSet()
//                ->canBeEnabled()
//            ->end()
//            ->arrayNode('async_commands')
//                ->addDefaultsIfNotSet()
//                ->canBeEnabled()
//            ->end()
//            ->arrayNode('extensions')->addDefaultsIfNotSet()->children()
//                ->booleanNode('doctrine_ping_connection_extension')->defaultFalse()->end()
//                ->booleanNode('doctrine_clear_identity_map_extension')->defaultFalse()->end()
//                ->booleanNode('signal_extension')->defaultValue(function_exists('pcntl_signal_dispatch'))->end()
//                ->booleanNode('reply_extension')->defaultTrue()->end()
//            ->end()->end()
//        ;
//
        return $tb;
    }
}
