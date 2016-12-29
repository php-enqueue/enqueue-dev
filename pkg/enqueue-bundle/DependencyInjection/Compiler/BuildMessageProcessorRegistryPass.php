<?php
namespace Enqueue\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildMessageProcessorRegistryPass implements CompilerPassInterface
{
    use ExtractMessageProcessorTagSubscriptionsTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processorTagName = 'enqueue.client.message_processor';
        $processorRegistryId = 'enqueue.client.message_processor_registry';

        if (false == $container->hasDefinition($processorRegistryId)) {
            return;
        }

        $processorIds = [];
        foreach ($container->findTaggedServiceIds($processorTagName) as $serviceId => $tagAttributes) {
            $subscriptions = $this->extractSubscriptions($container, $serviceId, $tagAttributes);

            foreach ($subscriptions as $subscription) {
                $processorIds[$subscription['processorName']] = $serviceId;
            }
        }

        $processorRegistryDef = $container->getDefinition($processorRegistryId);
        $processorRegistryDef->setArguments([$processorIds]);
    }
}
