<?php

namespace Enqueue\Symfony\DependencyInjection;

use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class BuildProcessorRoutesPass implements CompilerPassInterface
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
        $routeCollectionId = sprintf('enqueue.client.%s.route_collection', $this->name);
        if (false == $container->hasDefinition($routeCollectionId)) {
            return;
        }

        $tag = 'enqueue.processor';
        $routeCollection = new RouteCollection([]);
        foreach ($container->findTaggedServiceIds($tag) as $serviceId => $tagAttributes) {
            foreach ($tagAttributes as $tagAttribute) {
                $client = $tagAttribute['client'] ?? 'default';

                if ($client !== $this->name && 'all' !== $client) {
                    continue;
                }

                $topic = $tagAttribute['topic'] ?? null;
                $command = $tagAttribute['command'] ?? null;

                if (false == $topic && false == $command) {
                    throw new \LogicException('Either "topic" or "command" tag attribute must be set. None is set.');
                }
                if ($topic && $command) {
                    throw new \LogicException('Either "topic" or "command" tag attribute must be set. Both are set.');
                }

                $source = $command ?: $topic;
                $sourceType = $command ? Route::COMMAND : Route::TOPIC;
                $processor = $tagAttribute['processor'] ?? $serviceId;

                unset(
                    $tagAttribute['topic'],
                    $tagAttribute['command'],
                    $tagAttribute['source'],
                    $tagAttribute['source_type'],
                    $tagAttribute['processor'],
                    $tagAttribute['options']
                );
                $options = $tagAttribute;
                $options['processor_service_id'] = $serviceId;

                $routeCollection->add(new Route($source, $sourceType, $processor, $options));
            }
        }

        $rawRoutes = $routeCollection->toArray();

        $routeCollectionService = $container->getDefinition($routeCollectionId);
        $routeCollectionService->replaceArgument(0, array_merge(
            $routeCollectionService->getArgument(0),
            $rawRoutes
        ));
    }
}
