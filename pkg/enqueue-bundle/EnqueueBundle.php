<?php
namespace Enqueue\EnqueueBundle;

use Enqueue\AmqpExt\AmqpContext;
use Enqueue\AmqpExt\Symfony\AmqpTransportFactory;
use Enqueue\AmqpExt\Symfony\RabbitMqTransportFactory;
use Enqueue\Symfony\DefaultTransportFactory;
use Enqueue\Symfony\NullTransportFactory;
use Enqueue\EnqueueBundle\DependencyInjection\Compiler\BuildClientRoutingPass;
use Enqueue\EnqueueBundle\DependencyInjection\Compiler\BuildExtensionsPass;
use Enqueue\EnqueueBundle\DependencyInjection\Compiler\BuildMessageProcessorRegistryPass;
use Enqueue\EnqueueBundle\DependencyInjection\Compiler\BuildQueueMetaRegistryPass;
use Enqueue\EnqueueBundle\DependencyInjection\Compiler\BuildTopicMetaSubscribersPass;
use Enqueue\EnqueueBundle\DependencyInjection\EnqueueExtension;
use Enqueue\Stomp\StompContext;
use Enqueue\Stomp\Symfony\RabbitMqStompTransportFactory;
use Enqueue\Stomp\Symfony\StompTransportFactory;
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
        $container->addCompilerPass(new BuildMessageProcessorRegistryPass());
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
            $extension->addTransportFactory(new RabbitMqTransportFactory());
        }
    }
}
