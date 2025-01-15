<?php

namespace Enqueue\Tests\Symfony\Client\DependencyInjection;

use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Client\Route;
use Enqueue\Client\RouteCollection;
use Enqueue\Symfony\Client\DependencyInjection\BuildCommandSubscriberRoutesPass;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\Context;
use Interop\Queue\Message as InteropMessage;
use Interop\Queue\Processor;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class BuildCommandSubscriberRoutesPassTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementCompilerPassInterface()
    {
        $this->assertClassImplements(CompilerPassInterface::class, BuildCommandSubscriberRoutesPass::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(BuildCommandSubscriberRoutesPass::class);
    }

    public function testThrowIfEnqueueClientsParameterNotSet()
    {
        $pass = new BuildCommandSubscriberRoutesPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "enqueue.clients" parameter must be set.');
        $pass->process(new ContainerBuilder());
    }

    public function testThrowsIfNoRouteCollectionServiceFoundForConfiguredTransport()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo', 'bar']);
        $container->setParameter('enqueue.default_client', 'baz');

        $pass = new BuildCommandSubscriberRoutesPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Service "enqueue.client.foo.route_collection" not found');
        $pass->process($container);
    }

    public function testThrowIfTaggedProcessorIsBuiltByFactory()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['aName']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->register('enqueue.client.aName.route_collection', RouteCollection::class)
            ->addArgument([])
        ;
        $container->register('aProcessor', Processor::class)
            ->setFactory('foo')
            ->addTag('enqueue.command_subscriber')
        ;

        $pass = new BuildCommandSubscriberRoutesPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The command subscriber tag could not be applied to a service created by factory.');
        $pass->process($container);
    }

    public function testShouldRegisterProcessorWithMatchedName()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($this->createCommandSubscriberProcessor()))
            ->addTag('enqueue.command_subscriber', ['client' => 'foo'])
        ;
        $container->register('aProcessor', get_class($this->createCommandSubscriberProcessor()))
            ->addTag('enqueue.command_subscriber', ['client' => 'bar'])
        ;

        $pass = new BuildCommandSubscriberRoutesPass();

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
        $container->register('aFooProcessor', get_class($this->createCommandSubscriberProcessor()))
            ->addTag('enqueue.command_subscriber')
        ;
        $container->register('aProcessor', get_class($this->createCommandSubscriberProcessor()))
            ->addTag('enqueue.command_subscriber', ['client' => 'bar'])
        ;

        $pass = new BuildCommandSubscriberRoutesPass();

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
        $container->register('aFooProcessor', get_class($this->createCommandSubscriberProcessor()))
            ->addTag('enqueue.command_subscriber', ['client' => 'all'])
        ;
        $container->register('aProcessor', get_class($this->createCommandSubscriberProcessor()))
            ->addTag('enqueue.command_subscriber', ['client' => 'bar'])
        ;

        $pass = new BuildCommandSubscriberRoutesPass();

        $pass->process($container);

        self::assertIsArray($routeCollection->getArgument(0));
        $this->assertCount(1, $routeCollection->getArgument(0));
    }

    public function testShouldRegisterProcessorIfCommandsIsString()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $processor = $this->createCommandSubscriberProcessor('fooCommand');

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($processor))
            ->addTag('enqueue.command_subscriber')
        ;

        $pass = new BuildCommandSubscriberRoutesPass();
        $pass->process($container);

        self::assertIsArray($routeCollection->getArgument(0));
        $this->assertCount(1, $routeCollection->getArgument(0));

        $this->assertEquals(
            [
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

    public function testThrowIfCommandSubscriberReturnsNothing()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $processor = $this->createCommandSubscriberProcessor(null);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($processor))
            ->addTag('enqueue.command_subscriber')
        ;

        $pass = new BuildCommandSubscriberRoutesPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Command subscriber must return something.');
        $pass->process($container);
    }

    public function testShouldRegisterProcessorIfCommandsAreStrings()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $processor = $this->createCommandSubscriberProcessor(['fooCommand', 'barCommand']);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($processor))
            ->addTag('enqueue.command_subscriber')
        ;

        $pass = new BuildCommandSubscriberRoutesPass();
        $pass->process($container);

        self::assertIsArray($routeCollection->getArgument(0));
        $this->assertCount(2, $routeCollection->getArgument(0));

        $this->assertEquals(
            [
                [
                    'source' => 'fooCommand',
                    'source_type' => 'enqueue.client.command_route',
                    'processor' => 'aFooProcessor',
                    'processor_service_id' => 'aFooProcessor',
                ],
                [
                    'source' => 'barCommand',
                    'source_type' => 'enqueue.client.command_route',
                    'processor' => 'aFooProcessor',
                    'processor_service_id' => 'aFooProcessor',
                ],
            ],
            $routeCollection->getArgument(0)
        );
    }

    public function testShouldRegisterProcessorIfParamSingleCommandArray()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $processor = $this->createCommandSubscriberProcessor([
            'command' => 'fooCommand',
            'processor' => 'aCustomFooProcessorName',
            'anOption' => 'aFooVal',
        ]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($processor))
            ->addTag('enqueue.command_subscriber')
        ;

        $pass = new BuildCommandSubscriberRoutesPass();
        $pass->process($container);

        self::assertIsArray($routeCollection->getArgument(0));

        $this->assertCount(1, $routeCollection->getArgument(0));

        $this->assertEquals(
            [
                [
                    'source' => 'fooCommand',
                    'source_type' => 'enqueue.client.command_route',
                    'processor' => 'aCustomFooProcessorName',
                    'processor_service_id' => 'aFooProcessor',
                    'anOption' => 'aFooVal',
                ],
            ],
            $routeCollection->getArgument(0)
        );
    }

    public function testShouldRegisterProcessorIfCommandsAreParamArrays()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $processor = $this->createCommandSubscriberProcessor([
            ['command' => 'fooCommand', 'processor' => 'aCustomFooProcessorName', 'anOption' => 'aFooVal'],
            ['command' => 'barCommand', 'processor' => 'aCustomBarProcessorName', 'anOption' => 'aBarVal'],
        ]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($processor))
            ->addTag('enqueue.command_subscriber')
        ;

        $pass = new BuildCommandSubscriberRoutesPass();
        $pass->process($container);

        self::assertIsArray($routeCollection->getArgument(0));
        $this->assertCount(2, $routeCollection->getArgument(0));

        $this->assertEquals(
            [
                [
                    'source' => 'fooCommand',
                    'source_type' => 'enqueue.client.command_route',
                    'processor' => 'aCustomFooProcessorName',
                    'processor_service_id' => 'aFooProcessor',
                    'anOption' => 'aFooVal',
                ],
                [
                    'source' => 'barCommand',
                    'source_type' => 'enqueue.client.command_route',
                    'processor' => 'aCustomBarProcessorName',
                    'processor_service_id' => 'aFooProcessor',
                    'anOption' => 'aBarVal',
                ],
            ],
            $routeCollection->getArgument(0)
        );
    }

    public function testThrowIfCommandSubscriberParamsInvalid()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $processor = $this->createCommandSubscriberProcessor(['fooBar', true]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($processor))
            ->addTag('enqueue.command_subscriber')
        ;

        $pass = new BuildCommandSubscriberRoutesPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Command subscriber configuration is invalid');
        $pass->process($container);
    }

    public function testShouldMergeExtractedRoutesWithAlreadySetInCollection()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([
            (new Route('aCommand', Route::COMMAND, 'aProcessor'))->toArray(),
            (new Route('aCommand', Route::COMMAND, 'aProcessor'))->toArray(),
        ]);

        $processor = $this->createCommandSubscriberProcessor(['fooCommand']);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo']);
        $container->setParameter('enqueue.default_client', 'foo');
        $container->setDefinition('enqueue.client.foo.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($processor))
            ->addTag('enqueue.command_subscriber')
        ;

        $pass = new BuildCommandSubscriberRoutesPass();
        $pass->process($container);

        self::assertIsArray($routeCollection->getArgument(0));
        $this->assertCount(3, $routeCollection->getArgument(0));

        $this->assertEquals(
            [
                [
                    'source' => 'aCommand',
                    'source_type' => 'enqueue.client.command_route',
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

    public function testShouldRegister08CommandProcessor()
    {
        $routeCollection = new Definition(RouteCollection::class);
        $routeCollection->addArgument([]);

        $processor = $this->createCommandSubscriberProcessor([
            'processorName' => 'fooCommand',
            'queueName' => 'a_client_queue_name',
            'queueNameHardcoded' => true,
            'exclusive' => true,
            'anOption' => 'aFooVal',
        ]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['default']);
        $container->setParameter('enqueue.default_client', 'default');
        $container->setDefinition('enqueue.client.default.route_collection', $routeCollection);
        $container->register('aFooProcessor', get_class($processor))
            ->addTag('enqueue.command_subscriber')
        ;

        $pass = new BuildCommandSubscriberRoutesPass();
        $pass->process($container);

        self::assertIsArray($routeCollection->getArgument(0));
        $this->assertCount(1, $routeCollection->getArgument(0));

        $this->assertEquals(
            [
                [
                    'source' => 'fooCommand',
                    'source_type' => 'enqueue.client.command_route',
                    'processor' => 'aFooProcessor',
                    'processor_service_id' => 'aFooProcessor',
                    'anOption' => 'aFooVal',
                    'queue' => 'a_client_queue_name',
                    'prefix_queue' => false,
                ],
            ],
            $routeCollection->getArgument(0)
        );
    }

    private function createCommandSubscriberProcessor($commandSubscriberReturns = ['aCommand'])
    {
        $processor = new class() implements Processor, CommandSubscriberInterface {
            public static $return;

            public function process(InteropMessage $message, Context $context)
            {
            }

            public static function getSubscribedCommand()
            {
                return static::$return;
            }
        };

        $processor::$return = $commandSubscriberReturns;

        return $processor;
    }
}
