<?php

namespace Enqueue\Symfony\Client\DependencyInjection;

use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Symfony\DiUtils;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class BuildProcessorRoutesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (false == $container->hasParameter('enqueue.clients')) {
            throw new \LogicException('The "enqueue.clients" parameter must be set.');
        }

        $names = $container->getParameter('enqueue.clients');
        $defaultName = $container->getParameter('enqueue.default_client');

        foreach ($names as $name) {
            $diUtils = DiUtils::create(ClientFactory::MODULE, $name);
            $routeCollectionId = $diUtils->format('route_collection');
            if (false == $container->hasDefinition($routeCollectionId)) {
                throw new \LogicException(sprintf('Service "%s" not found', $routeCollectionId));
            }

            $tag = 'enqueue.processor';
            $routeCollection = new RouteCollection([]);
            foreach ($container->findTaggedServiceIds($tag) as $serviceId => $tagAttributes) {
                foreach ($tagAttributes as $tagAttribute) {
                    $client = $tagAttribute['client'] ?? $defaultName;

                    if ($client !== $name && 'all' !== $client) {
                        continue;
                    }

                    $topic = $tagAttribute['topic'] ?? null;
                    $command = $tagAttribute['command'] ?? null;

                    if (false == $topic && false == $command) {
                        throw new \LogicException(sprintf('Either "topic" or "command" tag attribute must be set on service "%s". None is set.', $serviceId));
                    }
                    if ($topic && $command) {
                        throw new \LogicException(sprintf('Either "topic" or "command" tag attribute must be set on service "%s". Both are set.', $serviceId));
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
}
