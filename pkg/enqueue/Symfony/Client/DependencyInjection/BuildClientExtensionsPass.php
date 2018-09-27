<?php

namespace Enqueue\Symfony\Client\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class BuildClientExtensionsPass implements CompilerPassInterface
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $clientName)
    {
        if (empty($clientName)) {
            throw new \InvalidArgumentException('The name could not be empty.');
        }

        $this->name = $clientName;
    }

    public function process(ContainerBuilder $container): void
    {
        $extensionsId = sprintf('enqueue.client.%s.client_extensions', $this->name);
        if (false == $container->hasDefinition($extensionsId)) {
            return;
        }

        $tags = array_merge(
            $container->findTaggedServiceIds('enqueue.client_extension'),
            $container->findTaggedServiceIds('enqueue.client.extension') // TODO BC
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
