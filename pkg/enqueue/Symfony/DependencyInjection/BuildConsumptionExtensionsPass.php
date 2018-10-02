<?php

namespace Enqueue\Symfony\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class BuildConsumptionExtensionsPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $transportName)
    {
        if (empty($transportName)) {
            throw new \InvalidArgumentException('The name could not be empty.');
        }

        $this->name = $transportName;
    }

    public function process(ContainerBuilder $container): void
    {
        $extensionsId = sprintf('enqueue.transport.%s.consumption_extensions', $this->name);
        if (false == $container->hasDefinition($extensionsId)) {
            return;
        }

        $tags = $container->findTaggedServiceIds('enqueue.transport.consumption_extension');

        $groupByPriority = [];
        foreach ($tags as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                $transport = $tagAttribute['transport'] ?? 'default';

                if ($transport !== $this->name && 'all' !== $transport) {
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
