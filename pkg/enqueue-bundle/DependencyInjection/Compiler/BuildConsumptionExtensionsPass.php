<?php

namespace Enqueue\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class BuildConsumptionExtensionsPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $tags = $container->findTaggedServiceIds('enqueue.consumption.extension');

        $groupByPriority = [];
        foreach ($tags as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                $priority = isset($tagAttribute['priority']) ? (int) $tagAttribute['priority'] : 0;

                $groupByPriority[$priority][] = new Reference($serviceId);
            }
        }

        krsort($groupByPriority, SORT_NUMERIC);

        $flatExtensions = [];
        foreach ($groupByPriority as $extension) {
            $flatExtensions = array_merge($flatExtensions, $extension);
        }

        $container->getDefinition('enqueue.consumption.extensions')->replaceArgument(0, $flatExtensions);
    }
}
