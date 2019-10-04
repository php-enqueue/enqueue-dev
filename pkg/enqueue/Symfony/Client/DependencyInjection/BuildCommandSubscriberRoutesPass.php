<?php

namespace Enqueue\Symfony\Client\DependencyInjection;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Symfony\DiUtils;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class BuildCommandSubscriberRoutesPass implements CompilerPassInterface
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

            $tag = 'enqueue.command_subscriber';
            $routeCollection = new RouteCollection([]);
            foreach ($container->findTaggedServiceIds($tag) as $serviceId => $tagAttributes) {
                $processorDefinition = $container->getDefinition($serviceId);
                if ($processorDefinition->getFactory()) {
                    throw new \LogicException('The command subscriber tag could not be applied to a service created by factory.');
                }

                $processorClass = $processorDefinition->getClass() ?? $serviceId;
                if (false == class_exists($processorClass)) {
                    throw new \LogicException(sprintf('The processor class "%s" could not be found.', $processorClass));
                }

                if (false == is_subclass_of($processorClass, CommandSubscriberInterface::class)) {
                    throw new \LogicException(sprintf('The processor must implement "%s" interface to be used with the tag "%s"', CommandSubscriberInterface::class, $tag));
                }

                foreach ($tagAttributes as $tagAttribute) {
                    $client = $tagAttribute['client'] ?? $defaultName;

                    if ($client !== $name && 'all' !== $client) {
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

                    // 0.8 command subscriber
                    if (isset($commands['processorName'])) {
                        @trigger_error('The command subscriber 0.8 syntax is deprecated since Enqueue 0.9.', E_USER_DEPRECATED);

                        $source = $commands['processorName'];
                        $processor = $params['processorName'] ?? $serviceId;

                        $options = $commands;
                        unset(
                            $options['processorName'],
                            $options['queueName'],
                            $options['queueNameHardcoded'],
                            $options['exclusive'],
                            $options['topic'],
                            $options['source'],
                            $options['source_type'],
                            $options['processor'],
                            $options['options']
                        );

                        $options['processor_service_id'] = $serviceId;

                        if (isset($commands['queueName'])) {
                            $options['queue'] = $commands['queueName'];
                        }

                        if (isset($commands['queueNameHardcoded']) && $commands['queueNameHardcoded']) {
                            $options['prefix_queue'] = false;
                        }

                        $routeCollection->add(new Route($source, Route::COMMAND, $processor, $options));

                        continue;
                    }

                    if (isset($commands['command'])) {
                        $commands = [$commands];
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
}
