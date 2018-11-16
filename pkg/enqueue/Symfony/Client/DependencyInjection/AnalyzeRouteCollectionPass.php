<?php

namespace Enqueue\Symfony\Client\DependencyInjection;

use Enqueue\Client\RouteCollection;
use Enqueue\Symfony\DiUtils;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AnalyzeRouteCollectionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (false == $container->hasParameter('enqueue.clients')) {
            throw new \LogicException('The "enqueue.clients" parameter must be set.');
        }

        $names = $container->getParameter('enqueue.clients');

        foreach ($names as $name) {
            $diUtils = DiUtils::create(ClientFactory::MODULE, $name);

            $routeCollectionId = $diUtils->format('route_collection');
            if (false == $container->hasDefinition($routeCollectionId)) {
                throw new \LogicException(sprintf('Service "%s" not found', $routeCollectionId));
            }

            $collection = RouteCollection::fromArray($container->getDefinition($routeCollectionId)->getArgument(0));

            $this->exclusiveCommandsCouldNotBeRunOnDefaultQueue($collection);
            $this->exclusiveCommandProcessorMustBeSingleOnGivenQueue($collection);
            $this->customQueueNamesUnique($collection);
            $this->defaultQueueMustBePrefixed($collection);
        }
    }

    private function exclusiveCommandsCouldNotBeRunOnDefaultQueue(RouteCollection $collection): void
    {
        foreach ($collection->all() as $route) {
            if ($route->isCommand() && $route->isProcessorExclusive() && false == $route->getQueue()) {
                throw new \LogicException(sprintf(
                    'The command "%s" processor "%s" is exclusive but queue is not specified. Exclusive processors could not be run on a default queue.',
                    $route->getSource(),
                    $route->getProcessor()
                ));
            }
        }
    }

    private function exclusiveCommandProcessorMustBeSingleOnGivenQueue(RouteCollection $collection): void
    {
        $prefixedQueues = [];
        $queues = [];
        foreach ($collection->all() as $route) {
            if (false == $route->isCommand()) {
                continue;
            }
            if (false == $route->isProcessorExclusive()) {
                continue;
            }

            if ($route->isPrefixQueue()) {
                if (array_key_exists($route->getQueue(), $prefixedQueues)) {
                    throw new \LogicException(sprintf(
                        'The command "%s" processor "%s" is exclusive. The queue "%s" already has another exclusive command processor "%s" bound to it.',
                        $route->getSource(),
                        $route->getProcessor(),
                        $route->getQueue(),
                        $prefixedQueues[$route->getQueue()]
                    ));
                }

                $prefixedQueues[$route->getQueue()] = $route->getProcessor();
            } else {
                if (array_key_exists($route->getQueue(), $queues)) {
                    throw new \LogicException(sprintf(
                        'The command "%s" processor "%s" is exclusive. The queue "%s" already has another exclusive command processor "%s" bound to it.',
                        $route->getSource(),
                        $route->getProcessor(),
                        $route->getQueue(),
                        $queues[$route->getQueue()]
                    ));
                }

                $queues[$route->getQueue()] = $route->getProcessor();
            }
        }
    }

    private function defaultQueueMustBePrefixed(RouteCollection $collection): void
    {
        foreach ($collection->all() as $route) {
            if (false == $route->getQueue() && false == $route->isPrefixQueue()) {
                throw new \LogicException('The default queue must be prefixed.');
            }
        }
    }

    private function customQueueNamesUnique(RouteCollection $collection): void
    {
        $prefixedQueues = [];
        $notPrefixedQueues = [];

        foreach ($collection->all() as $route) {
            //default queue
            $queueName = $route->getQueue();
            if (false == $queueName) {
                return;
            }

            $route->isPrefixQueue() ?
                $prefixedQueues[$queueName] = $queueName :
                $notPrefixedQueues[$queueName] = $queueName
            ;
        }

        foreach ($notPrefixedQueues as $queueName) {
            if (array_key_exists($queueName, $prefixedQueues)) {
                throw new \LogicException(sprintf('There are prefixed and not prefixed queue with the same name "%s". This is not allowed.', $queueName));
            }
        }
    }
}
