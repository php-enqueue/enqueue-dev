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
            $extension->addTransportFactory(new StompTransportFactory('stomp'));
            $extension->addTransportFactory(new RabbitMqStompTransportFactory('rabbitmq_stomp'));
        } else {
            $extension->addTransportFactory(new MissingTransportFactory('stomp', ['enqueue/stomp']));
            $extension->addTransportFactory(new MissingTransportFactory('rabbitmq_stomp', ['enqueue/stomp']));
        }

        if (
            class_exists(AmqpBunnyConnectionFactory::class) ||
            class_exists(AmqpExtConnectionFactory::class) ||
            class_exists(AmqpLibConnectionFactory::class)
        ) {
            $extension->addTransportFactory(new AmqpTransportFactory('amqp'));
            $extension->addTransportFactory(new RabbitMqAmqpTransportFactory('rabbitmq_amqp'));
        } else {
            $amppPackages = ['enqueue/stomp', 'enqueue/amqp-ext', 'enqueue/amqp-bunny', 'enqueue/amqp-lib'];
            $extension->addTransportFactory(new MissingTransportFactory('amqp', $amppPackages));
            $extension->addTransportFactory(new MissingTransportFactory('rabbitmq_amqp', $amppPackages));
        }

        if (class_exists(FsConnectionFactory::class)) {
            $extension->addTransportFactory(new FsTransportFactory('fs'));
        } else {
            $extension->addTransportFactory(new MissingTransportFactory('fs', ['enqueue/fs']));
        }

        if (class_exists(RedisConnectionFactory::class)) {
            $extension->addTransportFactory(new RedisTransportFactory('redis'));
        } else {
            $extension->addTransportFactory(new MissingTransportFactory('redis', ['enqueue/redis']));
        }

        if (class_exists(DbalConnectionFactory::class)) {
            $extension->addTransportFactory(new DbalTransportFactory('dbal'));
        } else {
            $extension->addTransportFactory(new MissingTransportFactory('dbal', ['enqueue/dbal']));
        }

        if (class_exists(SqsConnectionFactory::class)) {
            $extension->addTransportFactory(new SqsTransportFactory('sqs'));
        } else {
            $extension->addTransportFactory(new MissingTransportFactory('sqs', ['enqueue/sqs']));
        }

        if (class_exists(GpsConnectionFactory::class)) {
            $extension->addTransportFactory(new GpsTransportFactory('gps'));
        } else {
            $extension->addTransportFactory(new MissingTransportFactory('gps', ['enqueue/gps']));
        }

        $container->addCompilerPass(new AsyncEventsPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
        $container->addCompilerPass(new AsyncTransformersPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, 100);
    }
}
