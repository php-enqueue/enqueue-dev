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
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $tb = new TreeBuilder('enqueue_async_event_dispatcher');
            $rootNode = $tb->getRootNode();
        } else {
            $tb = new TreeBuilder();
            $rootNode = $tb->root('enqueue_async_event_dispatcher');
        }

        $rootNode->children()
            ->scalarNode('context_service')->isRequired()->cannotBeEmpty()->end()
        ;

        return $tb;
    }
}
