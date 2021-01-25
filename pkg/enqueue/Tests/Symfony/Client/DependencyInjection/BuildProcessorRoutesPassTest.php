<?php

namespace Enqueue\Tests\Symfony\Client\DependencyInjection;

use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Symfony\Client\DependencyInjection\BuildProcessorRoutesPass;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BuildProcessorRoutesPassTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementCompilerPassInterface()
    {
        $this->assertClassImplements(CompilerPassInterface::class, BuildProcessorRoutesPass::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(BuildProcessorRoutesPass::class);
    }

    public function testCouldBeConstructedWithoutArguments()
    {
        new BuildProcessorRoutesPass();
    }

    public function testThrowIfEnqueueClientsParameterNotSet()
    {
        $pass = new BuildProcessorRoutesPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "enqueue.clients" parameter must be set.');
        $pass->process(new ContainerBuilder());
    }

    public function testThrowsIfNoRouteCollectionServiceFoundForConfiguredTransport()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo', 'bar']);
        $container->setParameter('enqueue.default_client', 'baz');

        $pass = new BuildProcessorRoutesPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Service "enqueue.client.foo.route_collection" not found');
        $pass->process($container);
    }

    public function testThrowIfBothTopicAndCommandAttributesAreSet()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.processor', ['topic' => 'foo', 'command' => 'bar'])
        ;

        $pass = new BuildProcessorRoutesPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Either "topic" or "command" tag attribute must be set on service "aFooProcessor". Both are set.');
        $pass->process($container);
    }

    public function testThrowIfNeitherTopicNorCommandAttributesAreSet()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.processor', [])
        ;

        $pass = new BuildProcessorRoutesPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Either "topic" or "command" tag attribute must be set on service "aFooProcessor". None is set.');
        $pass->process($container);
    }

    public function testShouldRegisterProcessorWithMatchedName()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'bar');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.processor', ['client' => 'foo', 'topic' => 'foo'])
        ;
        $container->register('aProcessor', 'aProcessorClass')
            ->addTag('enqueue.processor', ['client' => 'bar', 'command' => 'foo'])
        ;

        $pass = new BuildProcessorRoutesPass();

        $pass->process($container);

        self::assertIsArray($routeCollection->getArgument(0));
        $this->assertCount(1, $routeCollection->getArgument(0));
    }

    public function testShouldRegisterProcessorWithoutNameToDefaultClient()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.processor', ['topic' => 'foo'])
        ;
        $container->register('aProcessor', 'aProcessorClass')
            ->addTag('enqueue.processor', ['client' => 'bar', 'command' => 'foo'])
        ;

        $pass = new BuildProcessorRoutesPass();

        $pass->process($container);

        self::assertIsArray($routeCollection->getArgument(0));
        $this->assertCount(1, $routeCollection->getArgument(0));
    }

    public function testShouldRegisterProcessorIfClientNameEqualsAll()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.processor', ['client' => 'all', 'topic' => 'foo'])
        ;
        $container->register('aProcessor', 'aProcessorClass')
            ->addTag('enqueue.processor', ['client' => 'bar', 'command' => 'foo'])
        ;

        $pass = new BuildProcessorRoutesPass();

        $pass->process($container);

        self::assertIsArray($routeCollection->getArgument(0));
        $this->assertCount(1, $routeCollection->getArgument(0));
    }

    public function testShouldRegisterAsTopicProcessor()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.processor', ['topic' => 'aTopic'])
        ;

        $pass = new BuildProcessorRoutesPass();
        $pass->process($container);

        self::assertIsArray($routeCollection->getArgument(0));
        $this->assertCount(1, $routeCollection->getArgument(0));

        $this->assertEquals(
            [
                [
                    'source' => 'aTopic',
                    'source_type' => 'enqueue.client.topic_route',
                    'processor' => 'aFooProcessor',
                    'processor_service_id' => 'aFooProcessor',
                ],
            ],
            $routeCollection->getArgument(0)
        );
    }

    public function testShouldRegisterAsCommandProcessor()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.processor', ['command' => 'aCommand'])
        ;

        $pass = new BuildProcessorRoutesPass();
        $pass->process($container);

        self::assertIsArray($routeCollection->getArgument(0));
        $this->assertCount(1, $routeCollection->getArgument(0));

        $this->assertEquals(
            [
                [
                    'source' => 'aCommand',
                    'source_type' => 'enqueue.client.command_route',
                    'processor' => 'aFooProcessor',
                    'processor_service_id' => 'aFooProcessor',
                ],
            ],
            $routeCollection->getArgument(0)
        );
    }

    public function testShouldRegisterWithCustomProcessorName()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.processor', ['command' => 'aCommand', 'processor' => 'customProcessorName'])
        ;

        $pass = new BuildProcessorRoutesPass();
        $pass->process($container);

        self::assertIsArray($routeCollection->getArgument(0));
        $this->assertCount(1, $routeCollection->getArgument(0));

        $this->assertEquals(
            [
                [
                    'source' => 'aCommand',
                    'source_type' => 'enqueue.client.command_route',
                    'processor' => 'customProcessorName',
                    'processor_service_id' => 'aFooProcessor',
                ],
            ],
            $routeCollection->getArgument(0)
        );
    }

    public function testShouldMergeExtractedRoutesWithAlreadySetInCollection()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([
            (new Route('aTopic', Route::TOPIC, 'aProcessor'))->toArray(),
            (new Route('aCommand', Route::COMMAND, 'aProcessor'))->toArray(),
        ]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.processor', ['command' => 'fooCommand'])
        ;

        $pass = new BuildProcessorRoutesPass();
        $pass->process($container);

        self::assertIsArray($routeCollection->getArgument(0));
        $this->assertCount(3, $routeCollection->getArgument(0));

        $this->assertEquals(
            [
                [
                    'source' => 'aTopic',
                    'source_type' => 'enqueue.client.topic_route',
                    'processor' => 'aProcessor',
                ],
                [
                    'source' => 'aCommand',
                    'source_type' => 'enqueue.client.command_route',
                    'processor' => 'aProcessor',
                ],
                [
                    'source' => 'fooCommand',
                    'source_type' => 'enqueue.client.command_route',
                    'processor' => 'aFooProcessor',
                    'processor_service_id' => 'aFooProcessor',
                ],
            ],
            $routeCollection->getArgument(0)
        );
    }
}
