<?php

namespace Enqueue\Bundle\DependencyInjection\Compiler;

use Enqueue\Client\Config;
use Enqueue\Client\RouterProcessor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildClientRoutingPass implements CompilerPassInterface
{
    use ExtractProcessorTagSubscriptionsTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processorTagName = 'enqueue.client.processor';
        $routerId = RouterProcessor::class;

        if (false == $container->hasDefinition($routerId)) {
            return;
        }

        $events = [];
        $commands = [];
        foreach ($container->findTaggedServiceIds($processorTagName) as $serviceId => $tagAttributes) {
            $subscriptions = $this->extractSubscriptions($container, $serviceId, $tagAttributes);

            foreach ($subscriptions as $subscription) {
                if (Config::COMMAND_TOPIC === $subscription['topicName']) {
                    $commands[$subscription['processorName']] = $subscription['queueName'];
                } else {
                    $events[$subscription['topicName']][] = [
                        $subscription['processorName'],
                        $subscription['queueName'],
                    ];
                }
            }
        }

        $router = $container->getDefinition($routerId);
        $router->replaceArgument(1, $events);
        $router->replaceArgument(2, $commands);
    }
}
