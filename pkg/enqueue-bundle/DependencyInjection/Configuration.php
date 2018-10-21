<?php

namespace Enqueue\Bundle\DependencyInjection;

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
            ->ifEmpty()->then(function () {
                return ['transport' => ['dsn' => 'null:']];
            });

        $transportFactory = new TransportFactory('default');

        $transportNode = $rootNode->children()->arrayNode('transport');
        $transportFactory->addTransportConfiguration($transportNode);

        $consumptionNode = $rootNode->children()->arrayNode('consumption');
        $transportFactory->addQueueConsumerConfiguration($consumptionNode);

        $clientFactory = new ClientFactory('default');
        $clientNode = $rootNode->children()->arrayNode('client');
        $clientFactory->addClientConfiguration($clientNode, $this->debug);

        $rootNode->children()
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
