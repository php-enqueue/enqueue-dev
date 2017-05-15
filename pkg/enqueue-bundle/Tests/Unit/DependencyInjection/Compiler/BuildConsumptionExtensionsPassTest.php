<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection\Compiler;

use Enqueue\Bundle\DependencyInjection\Compiler\BuildConsumptionExtensionsPass;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

class BuildConsumptionExtensionsPassTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementCompilerPass()
    {
        $this->assertClassImplements(CompilerPassInterface::class, BuildConsumptionExtensionsPass::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new BuildConsumptionExtensionsPass();
    }

    public function testShouldReplaceFirstArgumentOfExtensionsServiceConstructorWithTaggsExtensions()
    {
        $container = new ContainerBuilder();

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('enqueue.consumption.extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('enqueue.consumption.extension');
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.consumption.extension');
        $container->setDefinition('bar_extension', $extension);

        $pass = new BuildConsumptionExtensionsPass();
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
        $container->setDefinition('enqueue.consumption.extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('enqueue.consumption.extension', ['priority' => 6]);
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.consumption.extension', ['priority' => -5]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.consumption.extension', ['priority' => 2]);
        $container->setDefinition('baz_extension', $extension);

        $pass = new BuildConsumptionExtensionsPass();
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
        $container->setDefinition('enqueue.consumption.extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('enqueue.consumption.extension');
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.consumption.extension', ['priority' => 1]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.consumption.extension', ['priority' => -1]);
        $container->setDefinition('baz_extension', $extension);

        $pass = new BuildConsumptionExtensionsPass();
        $pass->process($container);

        $orderedExtensions = $extensions->getArgument(0);

        $this->assertEquals(new Reference('bar_extension'), $orderedExtensions[0]);
        $this->assertEquals(new Reference('foo_extension'), $orderedExtensions[1]);
        $this->assertEquals(new Reference('baz_extension'), $orderedExtensions[2]);
    }
}
