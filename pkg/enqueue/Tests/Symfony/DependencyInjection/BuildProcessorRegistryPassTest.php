<?php

namespace Enqueue\Tests\Symfony\DependencyInjection;

use Enqueue\Client\Route;
use Enqueue\Symfony\DependencyInjection\BuildProcessorRegistryPass;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BuildProcessorRegistryPassTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementCompilerPassInterface()
    {
        $this->assertClassImplements(CompilerPassInterface::class, BuildProcessorRegistryPass::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(BuildProcessorRegistryPass::class);
    }

    public function testCouldBeConstructedWithName()
    {
        $pass = new BuildProcessorRegistryPass('aName');

        $this->assertAttributeSame('aName', 'name', $pass);
    }

    public function testThrowIfNameEmptyOnConstruct()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The name could not be empty.');
        new BuildProcessorRegistryPass('');
    }

    public function testShouldDoNothingIfRouteCollectionServiceIsNotRegistered()
    {
        $container = new ContainerBuilder();

        //guard
        $this->assertFalse($container->hasDefinition('enqueue.client.aName.route_collection'));

        $pass = new BuildProcessorRegistryPass('aName');
        $pass->process($container);
    }

    public function testShouldDoNothingIfProcessorRegistryCollectionServiceIsNotRegistered()
    {
        $container = new ContainerBuilder();
        $container->register('enqueue.client.aName.route_collection');

        //guard
        $this->assertFalse($container->hasDefinition('enqueue.client.aName.processor_registry'));

        $pass = new BuildProcessorRegistryPass('aName');
        $pass->process($container);
    }

    public function testThrowIfProcessorServiceIdOptionNotSet()
    {
        $container = new ContainerBuilder();
        $container->register('enqueue.client.aName.route_collection')->addArgument([
            (new Route('aCommand', Route::COMMAND, 'aProcessor'))->toArray(),
        ]);
        $container->register('enqueue.client.aName.processor_registry')->addArgument([]);

        $pass = new BuildProcessorRegistryPass('aName');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The route option "processor_service_id" is required');
        $pass->process($container);
    }

    public function testShouldSetProcessorsMapToRegistryAsFirstArgument()
    {
        $registry = new Definition();
        $registry->addArgument([]);

        $container = new ContainerBuilder();
        $container->register('enqueue.client.aName.route_collection')->addArgument([
            (new Route(
                'aCommand',
                Route::COMMAND,
                'aBarProcessor',
                ['processor_service_id' => 'aBarServiceId']
            ))->toArray(),
            (new Route(
                'aTopic',
                Route::TOPIC,
                'aFooProcessor',
                ['processor_service_id' => 'aFooServiceId']
            ))->toArray(),
        ]);
        $container->setDefinition('enqueue.client.aName.processor_registry', $registry);

        $pass = new BuildProcessorRegistryPass('aName');
        $pass->process($container);

        $this->assertInternalType('array', $registry->getArgument(0));
        $this->assertEquals([
            'aBarProcessor' => 'aBarServiceId',
            'aFooProcessor' => 'aFooServiceId',
        ], $registry->getArgument(0));
    }

    public function testShouldMergeWithAddedPreviously()
    {
        $registry = new Definition();
        $registry->addArgument([
            'aBarProcessor' => 'aBarServiceIdAddedPreviously',
            'aOloloProcessor' => 'aOloloServiceIdAddedPreviously',
        ]);

        $container = new ContainerBuilder();
        $container->register('enqueue.client.aName.route_collection')->addArgument([
            (new Route(
                'aCommand',
                Route::COMMAND,
                'aBarProcessor',
                ['processor_service_id' => 'aBarServiceId']
            ))->toArray(),
            (new Route(
                'aTopic',
                Route::TOPIC,
                'aFooProcessor',
                ['processor_service_id' => 'aFooServiceId']
            ))->toArray(),
        ]);
        $container->setDefinition('enqueue.client.aName.processor_registry', $registry);

        $pass = new BuildProcessorRegistryPass('aName');
        $pass->process($container);

        $this->assertInternalType('array', $registry->getArgument(0));
        $this->assertEquals([
            'aOloloProcessor' => 'aOloloServiceIdAddedPreviously',
            'aBarProcessor' => 'aBarServiceId',
            'aFooProcessor' => 'aFooServiceId',
        ], $registry->getArgument(0));
    }
}
