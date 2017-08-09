<?php

namespace Enqueue\Bundle\DependencyInjection\Compiler;

use Enqueue\Client\Config;
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
        $routerId = 'enqueue.client.router_processor';

        if (false == $container->hasDefinition($routerId)) {
            return;
        }

        $configs = [];
        foreach ($container->findTaggedServiceIds($processorTagName) as $serviceId => $tagAttributes) {
            $subscriptions = $this->extractSubscriptions($container, $serviceId, $tagAttributes);

            foreach ($subscriptions as $subscription) {
                $configs[$subscription['topicName']][] = [
                    $subscription['processorName'],
                    $subscription['queueName'],
                ];
            }
        }

        $router = $container->getDefinition($routerId);
        $router->replaceArgument(1, $configs);

        if (isset($configs[Config::COMMAND_TOPIC])) {
            $commandRoutes = [];

            foreach ($configs[Config::COMMAND_TOPIC] as $command) {
                $commandRoutes[$command[0]] = $command[1];
            }

            $router->replaceArgument(2, $commandRoutes);
        }
    }
}
