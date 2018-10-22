<?php

namespace Enqueue\Symfony\Client\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class BuildConsumptionExtensionsPass implements CompilerPassInterface
{
    use FormatClientNameTrait;

    protected $name;

    public function process(ContainerBuilder $container): void
    {
        if (false == $container->hasParameter('enqueue.clients')) {
            throw new \LogicException('The "enqueue.clients" parameter must be set.');
        }

        $names = $container->getParameter('enqueue.clients');

        foreach ($names as $name) {
            $this->name = $name;

            $extensionsId = $this->format('consumption_extensions');
            if (false == $container->hasDefinition($extensionsId)) {
                throw new \LogicException(sprintf('Service "%s" not found', $extensionsId));
            }

            $tags = array_merge(
                $container->findTaggedServiceIds('enqueue.consumption_extension'),
                $container->findTaggedServiceIds('enqueue.consumption.extension') // TODO BC
            );

            $groupByPriority = [];
            foreach ($tags as $serviceId => $tagAttributes) {
                foreach ($tagAttributes as $tagAttribute) {
                    $client = $tagAttribute['client'] ?? 'default';

                    if ($client !== $this->name && 'all' !== $client) {
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

    protected function getName(): string
    {
        return $this->name;
    }
}
