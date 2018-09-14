<?php

namespace Enqueue\Bundle;

use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncEventDispatcherExtension;
use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncEventsPass;
use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncTransformersPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildClientExtensionsPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildConsumptionExtensionsPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildQueueMetaRegistryPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildTopicMetaSubscribersPass;
use Enqueue\Symfony\DependencyInjection\AnalyzeRouteCollectionPass;
use Enqueue\Symfony\DependencyInjection\BuildCommandSubscriberRoutesPass;
use Enqueue\Symfony\DependencyInjection\BuildProcessorRegistryPass;
use Enqueue\Symfony\DependencyInjection\BuildProcessorRoutesPass;
use Enqueue\Symfony\DependencyInjection\BuildTopicSubscriberRoutesPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EnqueueBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new BuildConsumptionExtensionsPass());
        $container->addCompilerPass(new BuildTopicMetaSubscribersPass());
        $container->addCompilerPass(new BuildQueueMetaRegistryPass());
        $container->addCompilerPass(new BuildClientExtensionsPass());

        $container->addCompilerPass(new BuildTopicSubscriberRoutesPass('default'), 100);
        $container->addCompilerPass(new BuildCommandSubscriberRoutesPass('default'), 100);
        $container->addCompilerPass(new BuildProcessorRoutesPass('default'), 100);
        $container->addCompilerPass(new AnalyzeRouteCollectionPass('default'), 30);
        $container->addCompilerPass(new BuildProcessorRegistryPass('default'));

        if (class_exists(AsyncEventDispatcherExtension::class)) {
            $container->addCompilerPass(new AsyncEventsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
            $container->addCompilerPass(new AsyncTransformersPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        }
    }
}
