<?php

namespace Enqueue\Symfony\DependencyInjection;

use Enqueue\Symfony\DiUtils;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class BuildConsumptionExtensionsPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (false == $container->hasParameter('enqueue.transports')) {
            throw new \LogicException('The "enqueue.transports" parameter must be set.');
        }

        $names = $container->getParameter('enqueue.transports');
        $defaultName = $container->getParameter('enqueue.default_transport');

        foreach ($names as $name) {
            $diUtils = DiUtils::create(TransportFactory::MODULE, $name);

            $extensionsId = $diUtils->format('consumption_extensions');
            if (false == $container->hasDefinition($extensionsId)) {
                throw new \LogicException(sprintf('Service "%s" not found', $extensionsId));
            }

            $tags = $container->findTaggedServiceIds('enqueue.transport.consumption_extension');

            $groupByPriority = [];
            foreach ($tags as $serviceId => $tagAttributes) {
                foreach ($tagAttributes as $tagAttribute) {
                    $transport = $tagAttribute['transport'] ?? $defaultName;

                    if ($transport !== $name && 'all' !== $transport) {
                        continue;
                    }

                    $priority = (int) ($tagAttribute['priority'] ?? 0);

                    $groupByPriority[$priority][] = new Reference($serviceId);
                }
            }

            krsort($groupByPriority, SORT_NUMERIC);

            $flatExtensions = [];
            foreach ($groupByPriority as $extension) {
                $flatExtensions = array_merge($flatExtensions, $extension);
            }

            $extensionsService = $container->getDefinition($extensionsId);
            $extensionsService->replaceArgument(0, array_merge(
                $extensionsService->getArgument(0),
                $flatExtensions
            ));
        }
    }
}
