<?php

namespace Enqueue\Bundle\DependencyInjection\Compiler;

use Enqueue\Client\Config;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildExclusiveCommandsExtensionPass implements CompilerPassInterface
{
    use ExtractProcessorTagSubscriptionsTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processorTagName = 'enqueue.client.processor';
        $extensionId = 'enqueue.client.exclusive_command_extension';
        if (false == $container->hasDefinition($extensionId)) {
            return;
        }

        $queueMetaRegistry = $container->getDefinition($extensionId);

        $queueNameToProcessorNameMap = [];
        foreach ($container->findTaggedServiceIds($processorTagName) as $serviceId => $tagAttributes) {
            $subscriptions = $this->extractSubscriptions($container, $serviceId, $tagAttributes);

            foreach ($subscriptions as $subscription) {
                if (Config::COMMAND_TOPIC != $subscription['topicName']) {
                    continue;
                }

                if (false == isset($subscription['exclusive']) || $subscription['exclusive'] === false) {
                    continue;
                }

                if (false == $subscription['queueNameHardcoded']) {
                    throw new \LogicException('The exclusive command could be used only with queueNameHardcoded attribute set to true.');
                }

                $queueNameToProcessorNameMap[$subscription['queueName']] = $subscription['processorName'];
            }
        }

        $queueMetaRegistry->replaceArgument(0, $queueNameToProcessorNameMap);
    }
}
