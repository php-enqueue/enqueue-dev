<?php

namespace Enqueue\Bundle;

use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpExt\Symfony\AmqpTransportFactory;
use Enqueue\AmqpExt\Symfony\RabbitMqAmqpTransportFactory;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildClientRoutingPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildExtensionsPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildProcessorRegistryPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildQueueMetaRegistryPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildTopicMetaSubscribersPass;
use Enqueue\Bundle\DependencyInjection\EnqueueExtension;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\Symfony\DbalTransportFactory;
use Enqueue\Fs\FsContext;
use Enqueue\Fs\Symfony\FsTransportFactory;
use Enqueue\Redis\RedisContext;
use Enqueue\Redis\Symfony\RedisTransportFactory;
use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\Symfony\RabbitMqStompTransportFactory;
use Enqueue\Stomp\Symfony\StompTransportFactory;
use Enqueue\Symfony\DefaultTransportFactory;
use Enqueue\Symfony\NullTransportFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class EnqueueBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new BuildExtensionsPass());
        $container->addCompilerPass(new BuildClientRoutingPass());
        $container->addCompilerPass(new BuildProcessorRegistryPass());
        $container->addCompilerPass(new BuildTopicMetaSubscribersPass());
        $container->addCompilerPass(new BuildQueueMetaRegistryPass());

        /** @var EnqueueExtension $extension */
        $extension = $container->getExtension('enqueue');
        $extension->addTransportFactory(new DefaultTransportFactory());
        $extension->addTransportFactory(new NullTransportFactory());

        if (class_exists(StompContext::class)) {
            $extension->addTransportFactory(new StompTransportFactory());
            $extension->addTransportFactory(new RabbitMqStompTransportFactory());
        }

        if (class_exists(AmqpContext::class)) {
            $extension->addTransportFactory(new AmqpTransportFactory());
            $extension->addTransportFactory(new RabbitMqAmqpTransportFactory());
        }

        if (class_exists(FsContext::class)) {
            $extension->addTransportFactory(new FsTransportFactory());
        }

        if (class_exists(RedisContext::class)) {
            $extension->addTransportFactory(new RedisTransportFactory());
        }

        if (class_exists(DbalContext::class)) {
            $extension->addTransportFactory(new DbalTransportFactory());
        }
    }
}
