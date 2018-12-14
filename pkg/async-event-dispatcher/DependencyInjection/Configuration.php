<?php

namespace Enqueue\AsyncEventDispatcher\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder('enqueue_async_event_dispatcher');
        $rootNode = $tb->getRootNode();

        $rootNode->children()
            ->scalarNode('context_service')->isRequired()->cannotBeEmpty()->end()
        ;

        return $tb;
    }
}
