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

    public function testCouldBeConstructedWithoutArguments()
    {
        new AnalyzeRouteCollectionPass();
    }

    public function testThrowIfEnqueueClientsParameterNotSet()
    {
        $pass = new AnalyzeRouteCollectionPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "enqueue.clients" parameter must be set.');
        $pass->process(new ContainerBuilder());
    }

    public function testThrowsIfNoRouteCollectionServiceFoundForConfiguredTransport()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo', 'bar']);

        $pass = new AnalyzeRouteCollectionPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Service "enqueue.client.foo.route_collection" not found');
        $pass->process($container);
    }

    public function testThrowIfExclusiveCommandProcessorOnDefaultQueue()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['aName']);
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
        $pass = new AnalyzeRouteCollectionPass();

        $pass->process($container);
    }

    public function testThrowIfTwoExclusiveCommandProcessorsWorkOnSamePrefixedQueue()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['aName']);
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
        $pass = new AnalyzeRouteCollectionPass();

        $pass->process($container);
    }

    public function testThrowIfTwoExclusiveCommandProcessorsWorkOnSameQueue()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['aName']);
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
        $pass = new AnalyzeRouteCollectionPass();

        $pass->process($container);
    }

    public function testThrowIfThereAreTwoQueuesWithSameNameAndOneNotPrefixed()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['aName']);
        $container->register('enqueue.client.aName.route_collection')->addArgument([
            (new Route(
                'aFooCommand',
                Route::COMMAND,
                'aFooProcessor',
                ['queue' => 'foo', 'prefix_queue' => false]
            ))->toArray(),

            (new Route(
                'aBarCommand',
                Route::COMMAND,
                'aBarProcessor',
                ['queue' => 'foo', 'prefix_queue' => true]
            ))->toArray(),
        ]);

        $pass = new AnalyzeRouteCollectionPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There are prefixed and not prefixed queue with the same name "foo". This is not allowed.');
        $pass->process($container);
    }

    public function testThrowIfDefaultQueueNotPrefixed()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['aName']);
        $container->register('enqueue.client.aName.route_collection')->addArgument([
            (new Route(
                'aFooCommand',
                Route::COMMAND,
                'aFooProcessor',
                ['queue' => null, 'prefix_queue' => false]
            ))->toArray(),
        ]);

        $pass = new AnalyzeRouteCollectionPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The default queue must be prefixed.');
        $pass->process($container);
    }
}
