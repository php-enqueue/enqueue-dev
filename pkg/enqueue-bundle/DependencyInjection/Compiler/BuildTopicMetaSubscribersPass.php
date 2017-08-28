<?php

namespace Enqueue\Bundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BuildTopicMetaSubscribersPass implements CompilerPassInterface
{
    use ExtractProcessorTagSubscriptionsTrait;

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $processorTagName = 'enqueue.client.processor';

        if (false == $container->hasDefinition('enqueue.client.meta.topic_meta_registry')) {
            return;
        }

        $topicsSubscribers = [];
        foreach ($container->findTaggedServiceIds($processorTagName) as $serviceId => $tagAttributes) {
            $subscriptions = $this->extractSubscriptions($container, $serviceId, $tagAttributes);

            foreach ($subscriptions as $subscription) {
                $topicsSubscribers[$subscription['topicName']][] = $subscription['processorName'];
            }
        }

        $addTopicMetaPass = AddTopicMetaPass::create();
        foreach ($topicsSubscribers as $topicName => $subscribers) {
            $addTopicMetaPass->add($topicName, '', $subscribers);
        }

        $addTopicMetaPass->process($container);
    }
}
