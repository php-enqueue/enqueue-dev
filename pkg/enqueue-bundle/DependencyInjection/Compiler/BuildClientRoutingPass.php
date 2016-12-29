<?php
namespace Enqueue\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildClientRoutingPass implements CompilerPassInterface
{
    use ExtractMessageProcessorTagSubscriptionsTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processorTagName = 'enqueue.client.message_processor';
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
    }
}
