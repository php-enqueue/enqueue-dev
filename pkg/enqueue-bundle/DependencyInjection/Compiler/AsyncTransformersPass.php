<?php

namespace Enqueue\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AsyncTransformersPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (false == $container->hasDefinition('enqueue.events.registry')) {
            return;
        }

        $map = [];
        foreach ($container->findTaggedServiceIds('enqueue.event_transformer') as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                $map[$tagAttribute['event']] = $serviceId;
            }
        }

        $container->getDefinition('enqueue.events.registry')
            ->replaceArgument(0, $map)
        ;
    }
}
