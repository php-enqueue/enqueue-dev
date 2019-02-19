<?php

namespace Enqueue\Bundle;

use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncEventDispatcherExtension;
use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncEventsPass;
use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncTransformersPass;
use Enqueue\Doctrine\DoctrineSchemaCompilerPass;
use Enqueue\Symfony\Client\DependencyInjection\AnalyzeRouteCollectionPass;
use Enqueue\Symfony\Client\DependencyInjection\BuildClientExtensionsPass;
use Enqueue\Symfony\Client\DependencyInjection\BuildCommandSubscriberRoutesPass as BuildClientCommandSubscriberRoutesPass;
use Enqueue\Symfony\Client\DependencyInjection\BuildConsumptionExtensionsPass as BuildClientConsumptionExtensionsPass;
use Enqueue\Symfony\Client\DependencyInjection\BuildProcessorRegistryPass as BuildClientProcessorRegistryPass;
use Enqueue\Symfony\Client\DependencyInjection\BuildProcessorRoutesPass as BuildClientProcessorRoutesPass;
use Enqueue\Symfony\Client\DependencyInjection\BuildTopicSubscriberRoutesPass as BuildClientTopicSubscriberRoutesPass;
use Enqueue\Symfony\DependencyInjection\BuildConsumptionExtensionsPass;
use Enqueue\Symfony\DependencyInjection\BuildProcessorRegistryPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EnqueueBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        //transport passes
        $container->addCompilerPass(new BuildConsumptionExtensionsPass());
        $container->addCompilerPass(new BuildProcessorRegistryPass());

        //client passes
        $container->addCompilerPass(new BuildClientConsumptionExtensionsPass());
        $container->addCompilerPass(new BuildClientExtensionsPass());
        $container->addCompilerPass(new BuildClientTopicSubscriberRoutesPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new BuildClientCommandSubscriberRoutesPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new BuildClientProcessorRoutesPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new AnalyzeRouteCollectionPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 30);
        $container->addCompilerPass(new BuildClientProcessorRegistryPass());

        if (class_exists(AsyncEventDispatcherExtension::class)) {
            $container->addCompilerPass(new AsyncEventsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
            $container->addCompilerPass(new AsyncTransformersPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        }

        $container->addCompilerPass(new DoctrineSchemaCompilerPass());
    }
}
