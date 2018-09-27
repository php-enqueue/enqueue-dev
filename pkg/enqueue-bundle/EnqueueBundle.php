<?php

namespace Enqueue\Bundle;

use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncEventDispatcherExtension;
use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncEventsPass;
use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncTransformersPass;
use Enqueue\Symfony\Client\DependencyInjection\AnalyzeRouteCollectionPass;
use Enqueue\Symfony\Client\DependencyInjection\BuildClientExtensionsPass;
use Enqueue\Symfony\Client\DependencyInjection\BuildCommandSubscriberRoutesPass;
use Enqueue\Symfony\Client\DependencyInjection\BuildConsumptionExtensionsPass;
use Enqueue\Symfony\Client\DependencyInjection\BuildProcessorRegistryPass;
use Enqueue\Symfony\Client\DependencyInjection\BuildProcessorRoutesPass;
use Enqueue\Symfony\Client\DependencyInjection\BuildTopicSubscriberRoutesPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EnqueueBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new BuildConsumptionExtensionsPass('default'));
        $container->addCompilerPass(new BuildClientExtensionsPass('default'));
        $container->addCompilerPass(new BuildTopicSubscriberRoutesPass('default'), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new BuildCommandSubscriberRoutesPass('default'), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new BuildProcessorRoutesPass('default'), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new AnalyzeRouteCollectionPass('default'), PassConfig::TYPE_BEFORE_OPTIMIZATION, 30);
        $container->addCompilerPass(new BuildProcessorRegistryPass('default'));

        if (class_exists(AsyncEventDispatcherExtension::class)) {
            $container->addCompilerPass(new AsyncEventsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
            $container->addCompilerPass(new AsyncTransformersPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        }
    }
}
