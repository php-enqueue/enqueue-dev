<?php

namespace Enqueue\Symfony\DependencyInjection;

use Enqueue\Client\RouteCollection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

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
            throw new \LogicException(sprintf('The required route collection "%s" is not registered. Make sure the client with name "%s" was loaded.', $routeCollectionId, $this->name));
        }

        $routeCollection = RouteCollection::fromArray($container->getDefinition($routeCollectionId)->getArgument(0));

        $map = [];
        foreach ($routeCollection->allRoutes() as $route) {
            if (false == $processorServiceId = $route->getOption('processor_service_id')) {
                throw new \LogicException('The route option "processor_service_id" is required');
            }

            $map[$route->getProcessor()] = $processorServiceId;
        }

        $registry = $container->getDefinition($processorRegistryId);
        $registry->replaceArgument(0, array_replace(
            $registry->getArgument(0),
            $map
        ));
    }
}
