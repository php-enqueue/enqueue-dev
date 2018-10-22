<?php

namespace Enqueue\Symfony\Client\DependencyInjection;

use Enqueue\Client\RouteCollection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class BuildProcessorRegistryPass implements CompilerPassInterface
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

            $processorRegistryId = $this->format('processor_registry');
            if (false == $container->hasDefinition($processorRegistryId)) {
                throw new \LogicException(sprintf('Service "%s" not found', $processorRegistryId));
            }

            $routeCollectionId = $this->format('route_collection');
            if (false == $container->hasDefinition($routeCollectionId)) {
                throw new \LogicException(sprintf('Service "%s" not found', $routeCollectionId));
            }

            $routerProcessorId = $this->format('router_processor');
            if (false == $container->hasDefinition($routerProcessorId)) {
                throw new \LogicException(sprintf('Service "%s" not found', $routerProcessorId));
            }

            $routeCollection = RouteCollection::fromArray($container->getDefinition($routeCollectionId)->getArgument(0));

            $map = [];
            foreach ($routeCollection->all() as $route) {
                if (false == $processorServiceId = $route->getOption('processor_service_id')) {
                    throw new \LogicException('The route option "processor_service_id" is required');
                }

                $map[$route->getProcessor()] = new Reference($processorServiceId);
            }

            $map[$this->parameter('router_processor')] = new Reference($routerProcessorId);

            $registry = $container->getDefinition($processorRegistryId);
            $registry->setArgument(0, ServiceLocatorTagPass::register($container, $map, $processorRegistryId));
        }
    }

    private function getName(): string
    {
        return $this->name;
    }
}
