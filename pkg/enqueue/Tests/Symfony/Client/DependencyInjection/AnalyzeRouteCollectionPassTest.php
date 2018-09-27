<?php

namespace Enqueue\Tests\Symfony\Client\DependencyInjection;

use Enqueue\Client\Route;
use Enqueue\Symfony\Client\DependencyInjection\AnalyzeRouteCollectionPass;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AnalyzeRouteCollectionPassTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementCompilerPass()
    {
        $this->assertClassImplements(CompilerPassInterface::class, AnalyzeRouteCollectionPass::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(AnalyzeRouteCollectionPass::class);
    }

    public function testCouldBeConstructedWithName()
    {
        $pass = new AnalyzeRouteCollectionPass('aName');

        $this->assertAttributeSame('aName', 'name', $pass);
    }

    public function testThrowIfNameEmptyOnConstruct()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The name could not be empty.');
        new AnalyzeRouteCollectionPass('');
    }

    public function testShouldDoNothingIfRouteCollectionServiceIsNotRegistered()
    {
        $pass = new AnalyzeRouteCollectionPass('aName');
        $pass->process(new ContainerBuilder());
    }

    public function testThrowIfExclusiveCommandProcessorOnDefaultQueue()
    {
        $container = new ContainerBuilder();
        $container->register('enqueue.client.aName.route_collection')->addArgument([
            (new Route(
                'aCommand',
                Route::COMMAND,
                'aBarProcessor',
                ['exclusive' => true]
            ))->toArray(),
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The command "aCommand" processor "aBarProcessor" is exclusive but queue is not specified. Exclusive processors could not be run on a default queue.');
        $pass = new AnalyzeRouteCollectionPass('aName');

        $pass->process($container);
    }

    public function testThrowIfTwoExclusiveCommandProcessorsWorkOnSamePrefixedQueue()
    {
        $container = new ContainerBuilder();
        $container->register('enqueue.client.aName.route_collection')->addArgument([
            (new Route(
                'aFooCommand',
                Route::COMMAND,
                'aFooProcessor',
                ['exclusive' => true, 'queue' => 'aQueue', 'prefix_queue' => true]
            ))->toArray(),

            (new Route(
                'aBarCommand',
                Route::COMMAND,
                'aBarProcessor',
                ['exclusive' => true, 'queue' => 'aQueue', 'prefix_queue' => true]
            ))->toArray(),
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The command "aBarCommand" processor "aBarProcessor" is exclusive. The queue "aQueue" already has another exclusive command processor "aFooProcessor" bound to it.');
        $pass = new AnalyzeRouteCollectionPass('aName');

        $pass->process($container);
    }

    public function testThrowIfTwoExclusiveCommandProcessorsWorkOnSameQueue()
    {
        $container = new ContainerBuilder();
        $container->register('enqueue.client.aName.route_collection')->addArgument([
            (new Route(
                'aFooCommand',
                Route::COMMAND,
                'aFooProcessor',
                ['exclusive' => true, 'queue' => 'aQueue', 'prefix_queue' => false]
            ))->toArray(),

            (new Route(
                'aBarCommand',
                Route::COMMAND,
                'aBarProcessor',
                ['exclusive' => true, 'queue' => 'aQueue', 'prefix_queue' => false]
            ))->toArray(),
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The command "aBarCommand" processor "aBarProcessor" is exclusive. The queue "aQueue" already has another exclusive command processor "aFooProcessor" bound to it.');
        $pass = new AnalyzeRouteCollectionPass('aName');

        $pass->process($container);
    }

    public function testShouldNotThrowIfTwoExclusiveCommandProcessorsWorkOnQueueWithSameNameButOnePrefixed()
    {
        $container = new ContainerBuilder();
        $container->register('enqueue.client.aName.route_collection')->addArgument([
            (new Route(
                'aFooCommand',
                Route::COMMAND,
                'aFooProcessor',
                ['exclusive' => true, 'queue' => 'aQueue', 'prefix_queue' => false]
            ))->toArray(),

            (new Route(
                'aBarCommand',
                Route::COMMAND,
                'aBarProcessor',
                ['exclusive' => true, 'queue' => 'aQueue', 'prefix_queue' => true]
            ))->toArray(),
        ]);

        $pass = new AnalyzeRouteCollectionPass('aName');

        $pass->process($container);
    }
}
