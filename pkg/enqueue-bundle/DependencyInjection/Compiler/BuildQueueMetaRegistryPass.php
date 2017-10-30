<?php

namespace Enqueue\Bundle\DependencyInjection\Compiler;

use Enqueue\Client\Meta\QueueMetaRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildQueueMetaRegistryPass implements CompilerPassInterface
{
    use ExtractProcessorTagSubscriptionsTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processorTagName = 'enqueue.client.processor';
        $queueMetaRegistryId = QueueMetaRegistry::class;
        if (false == $container->hasDefinition($queueMetaRegistryId)) {
            return;
        }

        $queueMetaRegistry = $container->getDefinition($queueMetaRegistryId);

        $configs = [];
        foreach ($container->findTaggedServiceIds($processorTagName) as $serviceId => $tagAttributes) {
            $subscriptions = $this->extractSubscriptions($container, $serviceId, $tagAttributes);

            foreach ($subscriptions as $subscription) {
                $configs[$subscription['queueName']]['processors'][] = $subscription['processorName'];

                if ($subscription['queueNameHardcoded']) {
                    $configs[$subscription['queueName']]['transportName'] = $subscription['queueName'];
                }
            }
        }

        $queueMetaRegistry->replaceArgument(1, $configs);
    }
}
