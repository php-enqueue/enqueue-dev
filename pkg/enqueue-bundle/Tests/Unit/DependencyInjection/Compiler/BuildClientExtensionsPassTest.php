<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler;

use Enqueue\Bundle\DependencyInjection\Compiler\BuildClientExtensionsPass;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BuildClientExtensionsPassTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementCompilerPass()
    {
        $this->assertClassImplements(CompilerPassInterface::class, BuildClientExtensionsPass::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new BuildClientExtensionsPass();
    }

    public function testShouldReplaceFirstArgumentOfExtensionsServiceConstructorWithTaggsExtensions()
    {
        $container = new ContainerBuilder();

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('enqueue.client.extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('enqueue.client.extension');
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.client.extension');
        $container->setDefinition('bar_extension', $extension);

        $pass = new BuildClientExtensionsPass();
        $pass->process($container);

        $this->assertEquals(
            [new Reference('foo_extension'), new Reference('bar_extension')],
            $extensions->getArgument(0)
        );
    }

    public function testShouldOrderExtensionsByPriority()
    {
        $container = new ContainerBuilder();

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('enqueue.client.extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('enqueue.client.extension', ['priority' => 6]);
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.client.extension', ['priority' => -5]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.client.extension', ['priority' => 2]);
        $container->setDefinition('baz_extension', $extension);

        $pass = new BuildClientExtensionsPass();
        $pass->process($container);

        $orderedExtensions = $extensions->getArgument(0);

        $this->assertEquals(new Reference('foo_extension'), $orderedExtensions[0]);
        $this->assertEquals(new Reference('baz_extension'), $orderedExtensions[1]);
        $this->assertEquals(new Reference('bar_extension'), $orderedExtensions[2]);
    }

    public function testShouldAssumePriorityZeroIfPriorityIsNotSet()
    {
        $container = new ContainerBuilder();

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('enqueue.client.extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('enqueue.client.extension');
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.client.extension', ['priority' => 1]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.client.extension', ['priority' => -1]);
        $container->setDefinition('baz_extension', $extension);

        $pass = new BuildClientExtensionsPass();
        $pass->process($container);

        $orderedExtensions = $extensions->getArgument(0);

        $this->assertEquals(new Reference('bar_extension'), $orderedExtensions[0]);
        $this->assertEquals(new Reference('foo_extension'), $orderedExtensions[1]);
        $this->assertEquals(new Reference('baz_extension'), $orderedExtensions[2]);
    }

    public function testShouldDoesNothingIfClientExtensionServiceIsNotDefined()
    {
        $container = $this->createMock(ContainerBuilder::class);
        $container
            ->expects($this->once())
            ->method('hasDefinition')
            ->with('enqueue.client.extensions')
            ->willReturn(false)
        ;
        $container
            ->expects($this->never())
            ->method('findTaggedServiceIds')
        ;

        $pass = new BuildClientExtensionsPass();
        $pass->process($container);
    }
}
