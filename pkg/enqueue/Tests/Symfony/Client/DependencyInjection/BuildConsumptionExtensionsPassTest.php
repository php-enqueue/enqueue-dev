<?php

namespace Enqueue\Tests\Symfony\Client\DependencyInjection;

use Enqueue\Client\ExtensionInterface;
use Enqueue\Symfony\Client\DependencyInjection\BuildConsumptionExtensionsPass;
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

    public function testCouldBeConstructedWithName()
    {
        $pass = new BuildConsumptionExtensionsPass('aName');

        $this->assertAttributeSame('aName', 'name', $pass);
    }

    public function testThrowIfNameEmptyOnConstruct()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The name could not be empty.');
        new BuildConsumptionExtensionsPass('');
    }

    public function testShouldDoNothingIfExtensionsServiceIsNotRegistered()
    {
        $container = new ContainerBuilder();

        //guard
        $this->assertFalse($container->hasDefinition('enqueue.client.aName.consumption_extensions'));

        $pass = new BuildConsumptionExtensionsPass('aName');
        $pass->process($container);
    }

    public function testShouldRegisterClientExtension()
    {
        $extensions = new Definition();
        $extensions->addArgument([]);

        $container = new ContainerBuilder();
        $container->setDefinition('enqueue.client.aName.consumption_extensions', $extensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.consumption_extension', ['client' => 'aName'])
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.consumption_extension', ['client' => 'aName'])
        ;

        $pass = new BuildConsumptionExtensionsPass('aName');
        $pass->process($container);

        $this->assertInternalType('array', $extensions->getArgument(0));
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
        $container->setDefinition('enqueue.client.aName.consumption_extensions', $extensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.consumption_extension', ['client' => 'aName'])
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.consumption_extension', ['client' => 'anotherName'])
        ;

        $pass = new BuildConsumptionExtensionsPass('aName');
        $pass->process($container);

        $this->assertInternalType('array', $extensions->getArgument(0));
        $this->assertEquals([
            new Reference('aFooExtension'),
        ], $extensions->getArgument(0));
    }

    public function testShouldAddExtensionIfClientAll()
    {
        $extensions = new Definition();
        $extensions->addArgument([]);

        $container = new ContainerBuilder();
        $container->setDefinition('enqueue.client.aName.consumption_extensions', $extensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.consumption_extension', ['client' => 'all'])
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.consumption_extension', ['client' => 'anotherName'])
        ;

        $pass = new BuildConsumptionExtensionsPass('aName');
        $pass->process($container);

        $this->assertInternalType('array', $extensions->getArgument(0));
        $this->assertEquals([
            new Reference('aFooExtension'),
        ], $extensions->getArgument(0));
    }

    public function testShouldTreatTagsWithoutClientAsDefaultClient()
    {
        $extensions = new Definition();
        $extensions->addArgument([]);

        $container = new ContainerBuilder();
        $container->setDefinition('enqueue.client.default.consumption_extensions', $extensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.consumption_extension')
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.consumption_extension')
        ;

        $pass = new BuildConsumptionExtensionsPass('default');
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

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('enqueue.client.default.consumption_extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('enqueue.consumption_extension', ['priority' => 6]);
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.consumption_extension', ['priority' => -5]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.consumption_extension', ['priority' => 2]);
        $container->setDefinition('baz_extension', $extension);

        $pass = new BuildConsumptionExtensionsPass('default');
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

        $extensions = new Definition();
        $extensions->addArgument([]);
        $container->setDefinition('enqueue.client.default.consumption_extensions', $extensions);

        $extension = new Definition();
        $extension->addTag('enqueue.consumption_extension');
        $container->setDefinition('foo_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.consumption_extension', ['priority' => 1]);
        $container->setDefinition('bar_extension', $extension);

        $extension = new Definition();
        $extension->addTag('enqueue.consumption_extension', ['priority' => -1]);
        $container->setDefinition('baz_extension', $extension);

        $pass = new BuildConsumptionExtensionsPass('default');
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
        $container->setDefinition('enqueue.client.aName.consumption_extensions', $extensions);

        $container->register('aFooExtension', ExtensionInterface::class)
            ->addTag('enqueue.consumption_extension')
        ;
        $container->register('aBarExtension', ExtensionInterface::class)
            ->addTag('enqueue.consumption_extension')
        ;

        $pass = new BuildConsumptionExtensionsPass('aName');
        $pass->process($container);

        $this->assertInternalType('array', $extensions->getArgument(0));
        $this->assertEquals([
            'aBarExtension' => 'aBarServiceIdAddedPreviously',
            'aOloloExtension' => 'aOloloServiceIdAddedPreviously',
        ], $extensions->getArgument(0));
    }
}
