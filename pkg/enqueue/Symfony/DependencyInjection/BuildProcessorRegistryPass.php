<?php

namespace Enqueue\Symfony\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class BuildProcessorRegistryPass implements CompilerPassInterface
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
        $processorRegistryId = sprintf('enqueue.transport.%s.processor_registry', $this->name);
        if (false == $container->hasDefinition($processorRegistryId)) {
            return;
        }

        $tag = 'enqueue.transport.processor';
        $map = [];
        foreach ($container->findTaggedServiceIds($tag) as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                $transport = $tagAttribute['transport'] ?? 'default';

                if ($transport !== $this->name && 'all' !== $transport) {
                    continue;
                }

                $processor = $tagAttribute['processor'] ?? $serviceId;

                $map[$processor] = new Reference($serviceId);
            }
        }

        $registry = $container->getDefinition($processorRegistryId);
        $registry->setArgument(0, ServiceLocatorTagPass::register($container, $map, $processorRegistryId));
    }
}
