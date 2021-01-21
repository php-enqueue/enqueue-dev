<?php

namespace Enqueue\Tests\Symfony\Client\DependencyInjection;

use Enqueue\Client\Route;
use Enqueue\Symfony\Client\DependencyInjection\BuildProcessorRegistryPass;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Argument\ServiceClosureArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

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

    public function testCouldBeConstructedWithoutArguments()
    {
        new BuildProcessorRegistryPass();
    }

    public function testThrowIfEnqueueClientsParameterNotSet()
    {
        $pass = new BuildProcessorRegistryPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "enqueue.clients" parameter must be set.');
        $pass->process(new ContainerBuilder());
    }

    public function testThrowsIfNoProcessorRegistryServiceFoundForConfiguredTransport()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo', 'bar']);

        $pass = new BuildProcessorRegistryPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Service "enqueue.client.foo.processor_registry" not found');
        $pass->process($container);
    }

    public function testThrowsIfNoRouteCollectionServiceFoundForConfiguredTransport()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo', 'bar']);
        $container->register('enqueue.client.foo.processor_registry');

        $pass = new BuildProcessorRegistryPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Service "enqueue.client.foo.route_collection" not found');
        $pass->process($container);
    }

    public function testThrowsIfNoRouteProcessorServiceFoundForConfiguredTransport()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['foo', 'bar']);
        $container->register('enqueue.client.foo.processor_registry');
        $container->register('enqueue.client.foo.route_collection');

        $pass = new BuildProcessorRegistryPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Service "enqueue.client.foo.router_processor" not found');
        $pass->process($container);
    }

    public function testThrowIfProcessorServiceIdOptionNotSet()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['aName']);
        $container->register('enqueue.client.aName.route_collection')->addArgument([
            (new Route('aCommand', Route::COMMAND, 'aProcessor'))->toArray(),
        ]);
        $container->register('enqueue.client.aName.processor_registry')->addArgument([]);
        $container->register('enqueue.client.aName.router_processor');

        $pass = new BuildProcessorRegistryPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The route option "processor_service_id" is required');
        $pass->process($container);
    }

    public function testShouldPassLocatorAsFirstArgument()
    {
        $registry = new Definition();
        $registry->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.clients', ['aName']);
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
        $container->register('enqueue.client.aName.router_processor');

        $pass = new BuildProcessorRegistryPass();
        $pass->process($container);

        $this->assertLocatorServices($container, $registry->getArgument(0), [
            '%enqueue.client.aName.router_processor%' => 'enqueue.client.aName.router_processor',
            'aBarProcessor' => 'aBarServiceId',
            'aFooProcessor' => 'aFooServiceId',
        ]);
    }

    private function assertLocatorServices(ContainerBuilder $container, $locatorId, array $locatorServices)
    {
        $this->assertInstanceOf(Reference::class, $locatorId);
        $locatorId = (string) $locatorId;

        $this->assertTrue($container->hasDefinition($locatorId));
        $this->assertMatchesRegularExpression('/\.?service_locator\..*?\.enqueue\./', $locatorId);

        $match = [];
        if (false == preg_match('/(\.?service_locator\..*?)\.enqueue\./', $locatorId, $match)) {
            $this->fail('preg_match should not failed');
        }

        $this->assertTrue($container->hasDefinition($match[1]));
        $locator = $container->getDefinition($match[1]);

        $this->assertContainsOnly(ServiceClosureArgument::class, $locator->getArgument(0));
        $actualServices = array_map(function (ServiceClosureArgument $value) {
            return (string) $value->getValues()[0];
        }, $locator->getArgument(0));

        $this->assertEquals($locatorServices, $actualServices);
    }
}
