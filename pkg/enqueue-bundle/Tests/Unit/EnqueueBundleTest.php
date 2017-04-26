<?php

namespace Enqueue\Bundle\Tests\Unit;

use Enqueue\AmqpExt\Symfony\AmqpTransportFactory;
use Enqueue\AmqpExt\Symfony\RabbitMqAmqpTransportFactory;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildClientRoutingPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildExtensionsPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildProcessorRegistryPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildQueueMetaRegistryPass;
use Enqueue\Bundle\DependencyInjection\Compiler\BuildTopicMetaSubscribersPass;
use Enqueue\Bundle\DependencyInjection\EnqueueExtension;
use Enqueue\Bundle\EnqueueBundle;
use Enqueue\Dbal\Symfony\DbalTransportFactory;
use Enqueue\Fs\Symfony\FsTransportFactory;
use Enqueue\Redis\Symfony\RedisTransportFactory;
use Enqueue\Sqs\Symfony\SqsTransportFactory;
use Enqueue\Stomp\Symfony\RabbitMqStompTransportFactory;
use Enqueue\Stomp\Symfony\StompTransportFactory;
use Enqueue\Symfony\DefaultTransportFactory;
use Enqueue\Symfony\NullTransportFactory;
use Enqueue\Test\ClassExtensionTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use PHPUnit\Framework\TestCase;

class EnqueueBundleTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldExtendBundleClass()
    {
        $this->assertClassExtends(Bundle::class, EnqueueBundle::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new EnqueueBundle();
    }

    public function testShouldRegisterExpectedCompilerPasses()
    {
        $extensionMock = $this->createMock(EnqueueExtension::class);

        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->at(0))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildExtensionsPass::class))
        ;
        $container
            ->expects($this->at(1))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildClientRoutingPass::class))
        ;
        $container
            ->expects($this->at(2))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildProcessorRegistryPass::class))
        ;
        $container
            ->expects($this->at(3))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildTopicMetaSubscribersPass::class))
        ;
        $container
            ->expects($this->at(4))
            ->method('addCompilerPass')
            ->with($this->isInstanceOf(BuildQueueMetaRegistryPass::class))
        ;
        $container
            ->expects($this->at(5))
            ->method('getExtension')
            ->willReturn($extensionMock)
        ;

        $bundle = new EnqueueBundle();
        $bundle->build($container);
    }

    public function testShouldRegisterDefaultAndNullTransportFactories()
    {
        $extensionMock = $this->createEnqueueExtensionMock();

        $container = new ContainerBuilder();
        $container->registerExtension($extensionMock);

        $extensionMock
            ->expects($this->at(0))
            ->method('addTransportFactory')
            ->with($this->isInstanceOf(DefaultTransportFactory::class))
        ;
        $extensionMock
            ->expects($this->at(1))
            ->method('addTransportFactory')
            ->with($this->isInstanceOf(NullTransportFactory::class))
        ;

        $bundle = new EnqueueBundle();
        $bundle->build($container);
    }

    public function testShouldRegisterStompAndRabbitMqStompTransportFactories()
    {
        $extensionMock = $this->createEnqueueExtensionMock();

        $container = new ContainerBuilder();
        $container->registerExtension($extensionMock);

        $extensionMock
            ->expects($this->at(2))
            ->method('addTransportFactory')
            ->with($this->isInstanceOf(StompTransportFactory::class))
        ;
        $extensionMock
            ->expects($this->at(3))
            ->method('addTransportFactory')
            ->with($this->isInstanceOf(RabbitMqStompTransportFactory::class))
        ;

        $bundle = new EnqueueBundle();
        $bundle->build($container);
    }

    public function testShouldRegisterAmqpAndRabbitMqAmqpTransportFactories()
    {
        $extensionMock = $this->createEnqueueExtensionMock();

        $container = new ContainerBuilder();
        $container->registerExtension($extensionMock);

        $extensionMock
            ->expects($this->at(4))
            ->method('addTransportFactory')
            ->with($this->isInstanceOf(AmqpTransportFactory::class))
        ;
        $extensionMock
            ->expects($this->at(5))
            ->method('addTransportFactory')
            ->with($this->isInstanceOf(RabbitMqAmqpTransportFactory::class))
        ;

        $bundle = new EnqueueBundle();
        $bundle->build($container);
    }

    public function testShouldRegisterFSTransportFactory()
    {
        $extensionMock = $this->createEnqueueExtensionMock();

        $container = new ContainerBuilder();
        $container->registerExtension($extensionMock);

        $extensionMock
            ->expects($this->at(6))
            ->method('addTransportFactory')
            ->with($this->isInstanceOf(FsTransportFactory::class))
        ;

        $bundle = new EnqueueBundle();
        $bundle->build($container);
    }

    public function testShouldRegisterRedisTransportFactory()
    {
        $extensionMock = $this->createEnqueueExtensionMock();

        $container = new ContainerBuilder();
        $container->registerExtension($extensionMock);

        $extensionMock
            ->expects($this->at(7))
            ->method('addTransportFactory')
            ->with($this->isInstanceOf(RedisTransportFactory::class))
        ;

        $bundle = new EnqueueBundle();
        $bundle->build($container);
    }

    public function testShouldRegisterDbalTransportFactory()
    {
        $extensionMock = $this->createEnqueueExtensionMock();

        $container = new ContainerBuilder();
        $container->registerExtension($extensionMock);

        $extensionMock
            ->expects($this->at(8))
            ->method('addTransportFactory')
            ->with($this->isInstanceOf(DbalTransportFactory::class))
        ;

        $bundle = new EnqueueBundle();
        $bundle->build($container);
    }

    public function testShouldRegisterSqsTransportFactory()
    {
        $extensionMock = $this->createEnqueueExtensionMock();

        $container = new ContainerBuilder();
        $container->registerExtension($extensionMock);

        $extensionMock
            ->expects($this->at(9))
            ->method('addTransportFactory')
            ->with($this->isInstanceOf(SqsTransportFactory::class))
        ;

        $bundle = new EnqueueBundle();
        $bundle->build($container);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|EnqueueExtension
     */
    private function createEnqueueExtensionMock()
    {
        $extensionMock = $this->createMock(EnqueueExtension::class);
        $extensionMock
            ->expects($this->once())
            ->method('getAlias')
            ->willReturn('enqueue')
        ;
        $extensionMock
            ->expects($this->once())
            ->method('getNamespace')
            ->willReturn(false)
        ;

        return $extensionMock;
    }
}
