<?php

namespace Enqueue\Symfony\DependencyInjection;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class BuildCommandSubscriberRoutesPass implements CompilerPassInterface
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

        $tag = 'enqueue.command_subscriber';
        $routeCollection = new RouteCollection([]);
        foreach ($container->findTaggedServiceIds($tag) as $serviceId => $tagAttributes) {
            $processorDefinition = $container->getDefinition($serviceId);
            if ($processorDefinition->getFactory()) {
                throw new \LogicException('The command subscriber tag could not be applied to a service created by factory.');
            }

            $processorClass = $processorDefinition->getClass();
            if (false == class_exists($processorClass)) {
                throw new \LogicException(sprintf('The processor class "%s" could not be found.', $processorClass));
            }

            if (false == is_subclass_of($processorClass, CommandSubscriberInterface::class)) {
                throw new \LogicException(sprintf('The processor must implement "%s" interface to be used with the tag "%s"', CommandSubscriberInterface::class, $tag));
            }

            foreach ($tagAttributes as $tagAttribute) {
                $client = $tagAttribute['client'] ?? 'default';

                if ($client !== $this->name && 'all' !== $client) {
                    continue;
                }

                /** @var CommandSubscriberInterface $processorClass */
                $commands = $processorClass::getSubscribedCommand();

                if (empty($commands)) {
                    throw new \LogicException('Command subscriber must return something.');
                }

                if (is_string($commands)) {
                    $commands = [$commands];
                }

                if (!is_array($commands)) {
                    throw new \LogicException('Command subscriber configuration is invalid. Should be an array or string.');
                }

                foreach ($commands as $key => $params) {
                    if (is_string($params)) {
                        $routeCollection->add(new Route($params, Route::COMMAND, $serviceId, ['processor_service_id' => $serviceId]));
                    } elseif (is_array($params)) {
                        $source = $params['command'] ?? null;
                        $processor = $params['processor'] ?? $serviceId;
                        unset($params['command'], $params['source'], $params['source_type'], $params['processor'], $params['options']);
                        $options = $params;
                        $options['processor_service_id'] = $serviceId;

                        $routeCollection->add(new Route($source, Route::COMMAND, $processor, $options));
                    } else {
                        throw new \LogicException(sprintf(
                            'Command subscriber configuration is invalid for "%s::getSubscribedCommand()". "%s"',
                            $processorClass,
                            json_encode($processorClass::getSubscribedCommand())
                        ));
                    }
                }
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
