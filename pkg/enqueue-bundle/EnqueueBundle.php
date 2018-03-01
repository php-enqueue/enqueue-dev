<?php

namespace Enqueue\Bundle;

use Enqueue\AmqpBunny\AmqpConnectionFactory as AmqpBunnyConnectionFactory;
use Enqueue\AmqpExt\AmqpConnectionFactory as AmqpExtConnectionFactory;
use Enqueue\AmqpLib\AmqpConnectionFactory as AmqpLibConnectionFactory;
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
use Enqueue\Gps\GpsConnectionFactory;
use Enqueue\Gps\Symfony\GpsTransportFactory;
use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Redis\Symfony\RedisTransportFactory;
use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\Symfony\SqsTransportFactory;
use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Stomp\Symfony\RabbitMqStompTransportFactory;
use Enqueue\Stomp\Symfony\StompTransportFactory;
use Enqueue\Symfony\AmqpTransportFactory;
use Enqueue\Symfony\MissingTransportFactory;
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
            $extension->setTransportFactory(new StompTransportFactory('stomp'));
            $extension->setTransportFactory(new RabbitMqStompTransportFactory('rabbitmq_stomp'));
        } else {
            $extension->setTransportFactory(new MissingTransportFactory('stomp', ['enqueue/stomp']));
            $extension->setTransportFactory(new MissingTransportFactory('rabbitmq_stomp', ['enqueue/stomp']));
        }

        if (
            class_exists(AmqpBunnyConnectionFactory::class) ||
            class_exists(AmqpExtConnectionFactory::class) ||
            class_exists(AmqpLibConnectionFactory::class)
        ) {
            $extension->setTransportFactory(new AmqpTransportFactory('amqp'));
            $extension->setTransportFactory(new RabbitMqAmqpTransportFactory('rabbitmq_amqp'));
        } else {
            $amppPackages = ['enqueue/amqp-ext', 'enqueue/amqp-bunny', 'enqueue/amqp-lib'];
            $extension->setTransportFactory(new MissingTransportFactory('amqp', $amppPackages));
            $extension->setTransportFactory(new MissingTransportFactory('rabbitmq_amqp', $amppPackages));
        }

        if (class_exists(FsConnectionFactory::class)) {
            $extension->setTransportFactory(new FsTransportFactory('fs'));
        } else {
            $extension->setTransportFactory(new MissingTransportFactory('fs', ['enqueue/fs']));
        }

        if (class_exists(RedisConnectionFactory::class)) {
            $extension->setTransportFactory(new RedisTransportFactory('redis'));
        } else {
            $extension->setTransportFactory(new MissingTransportFactory('redis', ['enqueue/redis']));
        }

        if (class_exists(DbalConnectionFactory::class)) {
            $extension->setTransportFactory(new DbalTransportFactory('dbal'));
        } else {
            $extension->setTransportFactory(new MissingTransportFactory('dbal', ['enqueue/dbal']));
        }

        if (class_exists(SqsConnectionFactory::class)) {
            $extension->setTransportFactory(new SqsTransportFactory('sqs'));
        } else {
            $extension->setTransportFactory(new MissingTransportFactory('sqs', ['enqueue/sqs']));
        }

        if (class_exists(GpsConnectionFactory::class)) {
            $extension->setTransportFactory(new GpsTransportFactory('gps'));
        } else {
            $extension->setTransportFactory(new MissingTransportFactory('gps', ['enqueue/gps']));
        }

        $container->addCompilerPass(new AsyncEventsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new AsyncTransformersPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
    }
}
