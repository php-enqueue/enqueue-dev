<?php

namespace Enqueue\Bundle;

use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpExt\Symfony\AmqpTransportFactory;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildClientExtensionsPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildClientRoutingPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildConsumptionExtensionsPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildProcessorRegistryPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildQueueMetaRegistryPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildTopicMetaSubscribersPass;
use Enqueue\Bundle\DependencyInjection\EnqueueExtension;
use Enqueue\Bundle\Events\DependencyInjection\AsyncEventsPass;
use Enqueue\Bundle\Events\DependencyInjection\AsyncTransformersPass;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use Enqueue\Dbal\Symfony\DbalTransportFactory;
use Enqueue\Fs\FsContext;
use Enqueue\Fs\Symfony\FsTransportFactory;
use Enqueue\Redis\RedisContext;
use Enqueue\Redis\Symfony\RedisTransportFactory;
use Enqueue\Sqs\SqsContext;
use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\Symfony\StompTransportFactory;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EnqueueBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new BuildConsumptionExtensionsPass());
        $container->addCompilerPass(new BuildClientRoutingPass());
        $container->addCompilerPass(new BuildProcessorRegistryPass());
        $container->addCompilerPass(new BuildTopicMetaSubscribersPass());
        $container->addCompilerPass(new BuildQueueMetaRegistryPass());
        $container->addCompilerPass(new BuildClientExtensionsPass());

        /** @var EnqueueExtension $extension */
        $extension = $container->getExtension('enqueue');

        if (class_exists(StompContext::class)) {
            $extension->addFactoryClass('stomp', StompTransportFactory::class);
        }

        if (class_exists(AmqpContext::class)) {
            $extension->addFactoryClass('amqp', AmqpTransportFactory::class);
        }

        if (class_exists(FsContext::class)) {
            $extension->addFactoryClass('file', FsTransportFactory::class);
        }

        if (class_exists(RedisContext::class)) {
            $extension->addFactoryClass('redis', RedisTransportFactory::class);
        }

        if (class_exists(DbalContext::class)) {
            $extension->addFactoryClass('dbal', DbalTransportFactory::class);
            $extension->addFactoryClass('doctrine', ManagerRegistryConnectionFactory::class);
        }

        if (class_exists(SqsContext::class)) {
            $extension->addFactoryClass('amazon_sqs', DbalTransportFactory::class);
        }

        $container->addCompilerPass(new AsyncEventsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new AsyncTransformersPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
    }
}
