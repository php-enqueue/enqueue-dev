<?php

namespace Enqueue\Tests\Symfony\Client\DependencyInjection;

use Enqueue\Client\ExtensionInterface;
use Enqueue\Symfony\Client\DependencyInjection\BuildClientExtensionsPass;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BuildClientExtensionsPassTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementCompilerPassInterface()
    {
        $this->assertClassImplements(CompilerPassInterface::class, BuildClientExtensionsPass::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(BuildClientExtensionsPass::class);
    }

    public function testCouldBeConstructedWithoutArguments()
    {
        new BuildClientExtensionsPass();
    }

    public function testThrowIfEnqueueClientsParameterNotSet()
    {
        $pass = new BuildClientExtensionsPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "enqueue.clients" parameter must be set.');
        $pass->process(new ContainerBuilder());
    }

    public function testThrowsIfNoClientExtensionsServiceFoundForConfiguredTransport()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo', 'bar']);
        $container->setParameter('enqueue.default_client', 'foo');

        $pass = new BuildClientExtensionsPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Service "enqueue.client.foo.client_extensions" not found');
        $pass->process($container);
    }

    public function testShouldRegisterClientExtension()
    {
        $extensions = new Definition();
        $extensions->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['aName']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.aName.client_extensions', $extensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.client_extension', ['client' => 'aName'])
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.client_extension', ['client' => 'aName'])
        ;

        $pass = new BuildClientExtensionsPass();
        $pass->process($container);

        self::assertIsArray($extensions->getArgument(0));
        $this->assertEquals([
            new Reference('aFooExtension'),
            new Reference('aBarExtension'),
        ], $extensions->getArgument(0));
    }

    public function testShouldIgnoreOtherClientExtensions()
    {
        $extensions = new Definition();
        $extensions->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['aName']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.aName.client_extensions', $extensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.client_extension', ['client' => 'aName'])
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.client_extension', ['client' => 'anotherName'])
        ;

        $pass = new BuildClientExtensionsPass();
        $pass->process($container);

        self::assertIsArray($extensions->getArgument(0));
        $this->assertEquals([
            new Reference('aFooExtension'),
        ], $extensions->getArgument(0));
    }

    public function testShouldAddExtensionIfClientAll()
    {
        $extensions = new Definition();
        $extensions->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['aName']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.aName.client_extensions', $extensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.client_extension', ['client' => 'all'])
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.client_extension', ['client' => 'anotherName'])
        ;

        $pass = new BuildClientExtensionsPass();
        $pass->process($container);

        self::assertIsArray($extensions->getArgument(0));
        $this->assertEquals([
            new Reference('aFooExtension'),
        ], $extensions->getArgument(0));
    }

    public function testShouldTreatTagsWithoutClientAsDefaultClient()
    {
        $extensions = new Definition();
        $extensions->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.client_extensions', $extensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.client_extension')
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.client_extension')
        ;

        $pass = new BuildClientExtensionsPass();
        $pass->process($container);

        self::assertIsArray($extensions->getArgument(0));
        $this->assertEquals([
            new Reference('aFooExtension'),
            new Reference('aBarExtension'),
        ], $extensions->getArgument(0));
    }

    public function testShouldOrderExtensionsByPriority()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('enqueue.client.foo.client_extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('enqueue.client_extension', ['priority' => 6]);
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.client_extension', ['priority' => -5]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.client_extension', ['priority' => 2]);
        $container->setDefinition('baz_extension', $extension);

        $pass = new BuildClientExtensionsPass();
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
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('enqueue.client.foo.client_extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('enqueue.client_extension');
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.client_extension', ['priority' => 1]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.client_extension', ['priority' => -1]);
        $container->setDefinition('baz_extension', $extension);

        $pass = new BuildClientExtensionsPass();
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
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.client_extensions', $extensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.client_extension')
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.client_extension')
        ;

        $pass = new BuildClientExtensionsPass();
        $pass->process($container);

        self::assertIsArray($extensions->getArgument(0));
        $this->assertCount(4, $extensions->getArgument(0));
    }

    public function testShouldRegisterProcessorWithMatchedNameToCorrespondingExtensions()
    {
        $fooExtensions = new Definition();
        $fooExtensions->addArgument([]);

        $barExtensions = new Definition();
        $barExtensions->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo', 'bar']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.client_extensions', $fooExtensions);
        $container->setDefinition('enqueue.client.bar.client_extensions', $barExtensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.client_extension', ['client' => 'foo'])
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.client_extension', ['client' => 'bar'])
        ;

        $pass = new BuildClientExtensionsPass();
        $pass->process($container);

        self::assertIsArray($fooExtensions->getArgument(0));
        $this->assertEquals([
            new Reference('aFooExtension'),
        ], $fooExtensions->getArgument(0));

        self::assertIsArray($barExtensions->getArgument(0));
        $this->assertEquals([
            new Reference('aBarExtension'),
        ], $barExtensions->getArgument(0));
    }
}
