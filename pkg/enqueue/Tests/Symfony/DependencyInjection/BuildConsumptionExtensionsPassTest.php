<?php

namespace Enqueue\Tests\Symfony\DependencyInjection;

use Enqueue\Client\ExtensionInterface;
use Enqueue\Symfony\DependencyInjection\BuildConsumptionExtensionsPass;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BuildConsumptionExtensionsPassTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementCompilerPassInterface()
    {
        $this->assertClassImplements(CompilerPassInterface::class, BuildConsumptionExtensionsPass::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(BuildConsumptionExtensionsPass::class);
    }

    public function testCouldBeConstructedWithoutArguments()
    {
        new BuildConsumptionExtensionsPass();
    }

    public function testThrowIfEnqueueTransportsParameterNotSet()
    {
        $pass = new BuildConsumptionExtensionsPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "enqueue.transports" parameter must be set.');
        $pass->process(new ContainerBuilder());
    }

    public function testThrowsIfNoConsumptionExtensionsServiceFoundForConfiguredTransport()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.transports', ['foo', 'bar']);

        $pass = new BuildConsumptionExtensionsPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Service "enqueue.transport.foo.consumption_extensions" not found');
        $pass->process($container);
    }

    public function testShouldRegisterTransportExtension()
    {
        $extensions = new Definition();
        $extensions->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.transports', ['aName']);
        $container->setDefinition('enqueue.transport.aName.consumption_extensions', $extensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.transport.consumption_extension', ['transport' => 'aName'])
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.transport.consumption_extension', ['transport' => 'aName'])
        ;

        $pass = new BuildConsumptionExtensionsPass();
        $pass->process($container);

        $this->assertInternalType('array', $extensions->getArgument(0));
        $this->assertEquals([
            new Reference('aFooExtension'),
            new Reference('aBarExtension'),
        ], $extensions->getArgument(0));
    }

    public function testShouldIgnoreOtherTransportExtensions()
    {
        $extensions = new Definition();
        $extensions->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.transports', ['aName']);
        $container->setDefinition('enqueue.transport.aName.consumption_extensions', $extensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.transport.consumption_extension', ['transport' => 'aName'])
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.transport.consumption_extension', ['transport' => 'anotherName'])
        ;

        $pass = new BuildConsumptionExtensionsPass();
        $pass->process($container);

        $this->assertInternalType('array', $extensions->getArgument(0));
        $this->assertEquals([
            new Reference('aFooExtension'),
        ], $extensions->getArgument(0));
    }

    public function testShouldAddExtensionIfTransportAll()
    {
        $extensions = new Definition();
        $extensions->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.transports', ['aName']);
        $container->setDefinition('enqueue.transport.aName.consumption_extensions', $extensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.transport.consumption_extension', ['transport' => 'all'])
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.transport.consumption_extension', ['transport' => 'anotherName'])
        ;

        $pass = new BuildConsumptionExtensionsPass();
        $pass->process($container);

        $this->assertInternalType('array', $extensions->getArgument(0));
        $this->assertEquals([
            new Reference('aFooExtension'),
        ], $extensions->getArgument(0));
    }

    public function testShouldTreatTagsWithoutTransportAsDefaultTransport()
    {
        $extensions = new Definition();
        $extensions->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.transports', ['default']);
        $container->setDefinition('enqueue.transport.default.consumption_extensions', $extensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.transport.consumption_extension')
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.transport.consumption_extension')
        ;

        $pass = new BuildConsumptionExtensionsPass();
        $pass->process($container);

        $this->assertInternalType('array', $extensions->getArgument(0));
        $this->assertEquals([
            new Reference('aFooExtension'),
            new Reference('aBarExtension'),
        ], $extensions->getArgument(0));
    }

    public function testShouldOrderExtensionsByPriority()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.transports', ['default']);

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('enqueue.transport.default.consumption_extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('enqueue.transport.consumption_extension', ['priority' => 6]);
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.transport.consumption_extension', ['priority' => -5]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.transport.consumption_extension', ['priority' => 2]);
        $container->setDefinition('baz_extension', $extension);

        $pass = new BuildConsumptionExtensionsPass();
        $pass->process($container);

        $orderedExtensions = $extensions->getArgument(0);

        $this->assertCount(3, $orderedExtensions);
        $this->assertEquals(new Reference('foo_extension'), $orderedExtensions[0]);
        $this->assertEquals(new Reference('baz_extension'), $orderedExtensions[1]);
        $this->assertEquals(new Reference('bar_extension'), $orderedExtensions[2]);
    }

    public function testShouldAssumePriorityZeroIfPriorityIsNotSet()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.transports', ['default']);

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('enqueue.transport.default.consumption_extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('enqueue.transport.consumption_extension');
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.transport.consumption_extension', ['priority' => 1]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.transport.consumption_extension', ['priority' => -1]);
        $container->setDefinition('baz_extension', $extension);

        $pass = new BuildConsumptionExtensionsPass();
        $pass->process($container);

        $orderedExtensions = $extensions->getArgument(0);

        $this->assertCount(3, $orderedExtensions);
        $this->assertEquals(new Reference('bar_extension'), $orderedExtensions[0]);
        $this->assertEquals(new Reference('foo_extension'), $orderedExtensions[1]);
        $this->assertEquals(new Reference('baz_extension'), $orderedExtensions[2]);
    }

    public function testShouldMergeWithAddedPreviously()
    {
        $extensions = new Definition();
        $extensions->addArgument([
            'aBarExtension' => 'aBarServiceIdAddedPreviously',
            'aOloloExtension' => 'aOloloServiceIdAddedPreviously',
        ]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.transports', ['aName']);
        $container->setDefinition('enqueue.transport.aName.consumption_extensions', $extensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.transport.consumption_extension')
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.transport.consumption_extension')
        ;

        $pass = new BuildConsumptionExtensionsPass();
        $pass->process($container);

        $this->assertInternalType('array', $extensions->getArgument(0));
        $this->assertEquals([
            'aBarExtension' => 'aBarServiceIdAddedPreviously',
            'aOloloExtension' => 'aOloloServiceIdAddedPreviously',
        ], $extensions->getArgument(0));
    }
}
