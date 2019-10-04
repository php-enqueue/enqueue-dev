<?php

namespace Enqueue\Symfony\Client\DependencyInjection;

use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Symfony\DiUtils;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class BuildTopicSubscriberRoutesPass implements CompilerPassInterface
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

            $tag = 'enqueue.topic_subscriber';
            $routeCollection = new RouteCollection([]);
            foreach ($container->findTaggedServiceIds($tag) as $serviceId => $tagAttributes) {
                $processorDefinition = $container->getDefinition($serviceId);
                if ($processorDefinition->getFactory()) {
                    throw new \LogicException('The topic subscriber tag could not be applied to a service created by factory.');
                }

                $processorClass = $processorDefinition->getClass() ?? $serviceId;
                if (false == class_exists($processorClass)) {
                    throw new \LogicException(sprintf('The processor class "%s" could not be found.', $processorClass));
                }

                if (false == is_subclass_of($processorClass, TopicSubscriberInterface::class)) {
                    throw new \LogicException(sprintf('The processor must implement "%s" interface to be used with the tag "%s"', TopicSubscriberInterface::class, $tag));
                }

                foreach ($tagAttributes as $tagAttribute) {
                    $client = $tagAttribute['client'] ?? $defaultName;

                    if ($client !== $name && 'all' !== $client) {
                        continue;
                    }

                    /** @var TopicSubscriberInterface $processorClass */
                    $topics = $processorClass::getSubscribedTopics();

                    if (empty($topics)) {
                        throw new \LogicException('Topic subscriber must return something.');
                    }

                    if (is_string($topics)) {
                        $topics = [$topics];
                    }

                    if (!is_array($topics)) {
                        throw new \LogicException('Topic subscriber configuration is invalid. Should be an array or string.');
                    }

                    foreach ($topics as $key => $params) {
                        if (is_string($params)) {
                            $routeCollection->add(new Route($params, Route::TOPIC, $serviceId, ['processor_service_id' => $serviceId]));

                        // 0.8 topic subscriber
                        } elseif (is_array($params) && is_string($key)) {
                            @trigger_error('The topic subscriber 0.8 syntax is deprecated since Enqueue 0.9.', E_USER_DEPRECATED);

                            $source = $key;
                            $processor = $params['processorName'] ?? $serviceId;

                            $options = $params;
                            unset(
                                $options['processorName'],
                                $options['queueName'],
                                $options['queueNameHardcoded'],
                                $options['topic'],
                                $options['source'],
                                $options['source_type'],
                                $options['processor'],
                                $options['options']
                            );

                            $options['processor_service_id'] = $serviceId;

                            if (isset($params['queueName'])) {
                                $options['queue'] = $params['queueName'];
                            }

                            if (isset($params['queueNameHardcoded']) && $params['queueNameHardcoded']) {
                                $options['prefix_queue'] = false;
                            }

                            $routeCollection->add(new Route($source, Route::TOPIC, $processor, $options));
                        } elseif (is_array($params)) {
                            $source = $params['topic'] ?? null;
                            $processor = $params['processor'] ?? $serviceId;
                            unset($params['topic'], $params['source'], $params['source_type'], $params['processor'], $params['options']);
                            $options = $params;
                            $options['processor_service_id'] = $serviceId;

                            $routeCollection->add(new Route($source, Route::TOPIC, $processor, $options));
                        } else {
                            throw new \LogicException(sprintf(
                                'Topic subscriber configuration is invalid for "%s::getSubscribedTopics()". Got "%s"',
                                $processorClass,
                                json_encode($processorClass::getSubscribedTopics())
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
