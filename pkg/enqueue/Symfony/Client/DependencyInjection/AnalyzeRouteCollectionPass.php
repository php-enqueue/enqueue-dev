<?php

namespace Enqueue\Symfony\Client\DependencyInjection;

use Enqueue\Client\RouteCollection;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class AnalyzeRouteCollectionPass implements CompilerPassInterface
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

        $collection = RouteCollection::fromArray($container->getDefinition($routeCollectionId)->getArgument(0));

        $this->exclusiveCommandsCouldNotBeRunOnDefaultQueue($collection);
        $this->exclusiveCommandProcessorMustBeSingleOnGivenQueue($collection);
    }

    private function exclusiveCommandsCouldNotBeRunOnDefaultQueue(RouteCollection $collection)
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

    private function exclusiveCommandProcessorMustBeSingleOnGivenQueue(RouteCollection $collection)
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
}
