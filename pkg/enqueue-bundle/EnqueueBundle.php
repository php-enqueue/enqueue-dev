<?php

namespace Enqueue\Bundle;

use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncEventsPass;
use Enqueue\AsyncEventDispatcher\DependencyInjection\AsyncTransformersPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildClientExtensionsPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildClientRoutingPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildConsumptionExtensionsPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildExclusiveCommandsExtensionPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildProcessorRegistryPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildQueueMetaRegistryPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildTopicMetaSubscribersPass;
use Enqueue\Bundle\DependencyInjection\EnqueueExtension;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\Symfony\DbalTransportFactory;
use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\Symfony\FsTransportFactory;
use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Redis\Symfony\RedisTransportFactory;
use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\Symfony\SqsTransportFactory;
use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Stomp\Symfony\RabbitMqStompTransportFactory;
use Enqueue\Stomp\Symfony\StompTransportFactory;
use Enqueue\Symfony\AmqpTransportFactory;
use Enqueue\Symfony\RabbitMqAmqpTransportFactory;
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
        $container->addCompilerPass(new BuildExclusiveCommandsExtensionPass());

        /** @var EnqueueExtension $extension */
        $extension = $container->getExtension('enqueue');

        if (class_exists(StompConnectionFactory::class)) {
            $extension->addTransportFactory(new StompTransportFactory());
            $extension->addTransportFactory(new RabbitMqStompTransportFactory());
        }

        $extension->addTransportFactory(new AmqpTransportFactory('amqp'));
        $extension->addTransportFactory(new RabbitMqAmqpTransportFactory('rabbitmq_amqp'));

        if (class_exists(FsConnectionFactory::class)) {
            $extension->addTransportFactory(new FsTransportFactory());
        }

        if (class_exists(RedisConnectionFactory::class)) {
            $extension->addTransportFactory(new RedisTransportFactory());
        }

        if (class_exists(DbalConnectionFactory::class)) {
            $extension->addTransportFactory(new DbalTransportFactory());
        }

        if (class_exists(SqsConnectionFactory::class)) {
            $extension->addTransportFactory(new SqsTransportFactory());
        }

        $container->addCompilerPass(new AsyncEventsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new AsyncTransformersPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
    }
}
