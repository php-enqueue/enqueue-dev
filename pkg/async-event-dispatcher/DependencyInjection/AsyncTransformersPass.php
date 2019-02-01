<?php

namespace Enqueue\AsyncEventDispatcher\DependencyInjection;

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

        $transformerIdsMap = [];
        $eventNamesMap = [];
        $defaultTransformer = null;
        foreach ($container->findTaggedServiceIds('enqueue.event_transformer') as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                if (false == isset($tagAttribute['eventName'])) {
                    throw new \LogicException('The eventName attribute must be set');
                }

                $eventName = $tagAttribute['eventName'];

                $transformerName = isset($tagAttribute['transformerName']) ? $tagAttribute['transformerName'] : $serviceId;

                if (isset($tagAttribute['default']) && $tagAttribute['default']) {
                    $defaultTransformer = ['id' => $serviceId, 'transformerName' => $transformerName];
                } else {
                    $eventNamesMap[] = $transformerName;
                    $transformerIdsMap[] = $serviceId;
                }
            }
        }

        if ($defaultTransformer) {
            $eventNamesMap[] = $defaultTransformer['transformerName'];
            $transformerIdsMap[] = $defaultTransformer['id'];
        }

        $container->getDefinition('enqueue.events.registry')
            ->replaceArgument(0, $eventNamesMap)
            ->replaceArgument(1, $transformerIdsMap)
        ;
    }
}
