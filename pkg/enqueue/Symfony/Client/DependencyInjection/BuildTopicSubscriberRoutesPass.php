<?php

namespace Enqueue\Symfony\Client\DependencyInjection;

use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Client\TopicSubscriberInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class BuildTopicSubscriberRoutesPass implements CompilerPassInterface
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

        $tag = 'enqueue.topic_subscriber';
        $routeCollection = new RouteCollection([]);
        foreach ($container->findTaggedServiceIds($tag) as $serviceId => $tagAttributes) {
            $processorDefinition = $container->getDefinition($serviceId);
            if ($processorDefinition->getFactory()) {
                throw new \LogicException('The topic subscriber tag could not be applied to a service created by factory.');
            }

            $processorClass = $processorDefinition->getClass();
            if (false == class_exists($processorClass)) {
                throw new \LogicException(sprintf('The processor class "%s" could not be found.', $processorClass));
            }

            if (false == is_subclass_of($processorClass, TopicSubscriberInterface::class)) {
                throw new \LogicException(sprintf('The processor must implement "%s" interface to be used with the tag "%s"', TopicSubscriberInterface::class, $tag));
            }

            foreach ($tagAttributes as $tagAttribute) {
                $client = $tagAttribute['client'] ?? 'default';

                if ($client !== $this->name && 'all' !== $client) {
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
