<?php

namespace Enqueue\Symfony\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class BuildProcessorRegistryPass implements CompilerPassInterface
{
    use FormatTransportNameTrait;

    protected $name;

    public function process(ContainerBuilder $container): void
    {
        if (false == $container->hasParameter('enqueue.transports')) {
            throw new \LogicException('The "enqueue.transports" parameter must be set.');
        }

        $names = $container->getParameter('enqueue.transports');

        foreach ($names as $name) {
            $this->name = $name;

            $processorRegistryId = $this->format('processor_registry');
            if (false == $container->hasDefinition($processorRegistryId)) {
                throw new \LogicException(sprintf('Service "%s" not found', $processorRegistryId));
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

    protected function getName(): string
    {
        return $this->name;
    }
}
