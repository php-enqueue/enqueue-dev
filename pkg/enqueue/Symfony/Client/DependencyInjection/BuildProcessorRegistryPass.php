<?php

namespace Enqueue\Symfony\Client\DependencyInjection;

use Enqueue\Client\RouteCollection;
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

    public function __construct(string $clientName)
    {
        if (empty($clientName)) {
            throw new \InvalidArgumentException('The name could not be empty.');
        }

        $this->name = $clientName;
    }

    public function process(ContainerBuilder $container): void
    {
        $processorRegistryId = sprintf('enqueue.client.%s.processor_registry', $this->name);
        if (false == $container->hasDefinition($processorRegistryId)) {
            return;
        }

        $routeCollectionId = sprintf('enqueue.client.%s.route_collection', $this->name);
        if (false == $container->hasDefinition($routeCollectionId)) {
            return;
        }

        $routerProcessorId = sprintf('enqueue.client.%s.router_processor', $this->name);
        if (false == $container->hasDefinition($routerProcessorId)) {
            return;
        }

        $routeCollection = RouteCollection::fromArray($container->getDefinition($routeCollectionId)->getArgument(0));

        $map = [];
        foreach ($routeCollection->all() as $route) {
            if (false == $processorServiceId = $route->getOption('processor_service_id')) {
                throw new \LogicException('The route option "processor_service_id" is required');
            }

            $map[$route->getProcessor()] = new Reference($processorServiceId);
        }

        $map["%enqueue.client.{$this->name}.router_processor%"] = new Reference($routerProcessorId);

        $registry = $container->getDefinition($processorRegistryId);
        $registry->setArgument(0, ServiceLocatorTagPass::register($container, $map, $processorRegistryId));
    }

    private function getName(): string
    {
        return $this->name;
    }
}
