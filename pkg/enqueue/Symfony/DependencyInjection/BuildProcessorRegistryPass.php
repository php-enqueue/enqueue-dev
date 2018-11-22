<?php

namespace Enqueue\Symfony\DependencyInjection;

use Enqueue\Symfony\DiUtils;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class BuildProcessorRegistryPass implements CompilerPassInterface
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

            $processorRegistryId = $diUtils->format('processor_registry');
            if (false == $container->hasDefinition($processorRegistryId)) {
                throw new \LogicException(sprintf('Service "%s" not found', $processorRegistryId));
            }

            $tag = 'enqueue.transport.processor';
            $map = [];
            foreach ($container->findTaggedServiceIds($tag) as $serviceId => $tagAttributes) {
                foreach ($tagAttributes as $tagAttribute) {
                    $transport = $tagAttribute['transport'] ?? $defaultName;

                    if ($transport !== $name && 'all' !== $transport) {
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
}
