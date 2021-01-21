<?php

namespace Enqueue\Tests\Symfony\DependencyInjection;

use Enqueue\ProcessorRegistryInterface;
use Enqueue\Symfony\DependencyInjection\BuildProcessorRegistryPass;
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

    public function testThrowIfEnqueueTransportsParameterNotSet()
    {
        $pass = new BuildProcessorRegistryPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The "enqueue.transports" parameter must be set.');
        $pass->process(new ContainerBuilder());
    }

    public function testThrowsIfNoRegistryServiceFoundForConfiguredTransport()
    {
        $container = new ContainerBuilder();
        $container->setParameter('enqueue.transports', ['foo', 'bar']);
        $container->setParameter('enqueue.default_transport', 'baz');

        $pass = new BuildProcessorRegistryPass();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Service "enqueue.transport.foo.processor_registry" not found');
        $pass->process($container);
    }

    public function testShouldRegisterProcessorWithMatchedName()
    {
        $registry = new Definition(ProcessorRegistryInterface::class);
        $registry->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.transports', ['foo']);
        $container->setParameter('enqueue.default_transport', 'foo');
        $container->setDefinition('enqueue.transport.foo.processor_registry', $registry);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', ['transport' => 'foo'])
        ;
        $container->register('aProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', ['transport' => 'bar'])
        ;

        $pass = new BuildProcessorRegistryPass();

        $pass->process($container);

        $this->assertInstanceOf(Reference::class, $registry->getArgument(0));

        $this->assertLocatorServices($container, $registry->getArgument(0), [
            'aFooProcessor' => 'aFooProcessor',
        ]);
    }

    public function testShouldRegisterProcessorWithMatchedNameToCorrespondingRegistries()
    {
        $fooRegistry = new Definition(ProcessorRegistryInterface::class);
        $fooRegistry->addArgument([]);

        $barRegistry = new Definition(ProcessorRegistryInterface::class);
        $barRegistry->addArgument([]);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.transports', ['foo', 'bar']);
        $container->setParameter('enqueue.default_transport', 'foo');
        $container->setDefinition('enqueue.transport.foo.processor_registry', $fooRegistry);
        $container->setDefinition('enqueue.transport.bar.processor_registry', $barRegistry);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', ['transport' => 'foo'])
        ;
        $container->register('aBarProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', ['transport' => 'bar'])
        ;

        $pass = new BuildProcessorRegistryPass();

        $pass->process($container);

        $this->assertInstanceOf(Reference::class, $fooRegistry->getArgument(0));
        $this->assertLocatorServices($container, $fooRegistry->getArgument(0), [
            'aFooProcessor' => 'aFooProcessor',
        ]);

        $this->assertInstanceOf(Reference::class, $barRegistry->getArgument(0));
        $this->assertLocatorServices($container, $barRegistry->getArgument(0), [
            'aBarProcessor' => 'aBarProcessor',
        ]);
    }

    public function testShouldRegisterProcessorWithoutNameToDefaultTransport()
    {
        $registry = new Definition(ProcessorRegistryInterface::class);
        $registry->addArgument(null);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.transports', ['foo']);
        $container->setParameter('enqueue.default_transport', 'foo');
        $container->setDefinition('enqueue.transport.foo.processor_registry', $registry);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', [])
        ;
        $container->register('aProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', ['transport' => 'bar'])
        ;

        $pass = new BuildProcessorRegistryPass();

        $pass->process($container);

        $this->assertInstanceOf(Reference::class, $registry->getArgument(0));

        $this->assertLocatorServices($container, $registry->getArgument(0), [
            'aFooProcessor' => 'aFooProcessor',
        ]);
    }

    public function testShouldRegisterProcessorIfTransportNameEqualsAll()
    {
        $registry = new Definition(ProcessorRegistryInterface::class);
        $registry->addArgument(null);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.transports', ['foo']);
        $container->setParameter('enqueue.default_transport', 'foo');
        $container->setDefinition('enqueue.transport.foo.processor_registry', $registry);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', ['transport' => 'all'])
        ;
        $container->register('aProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', ['transport' => 'bar'])
        ;

        $pass = new BuildProcessorRegistryPass();

        $pass->process($container);

        $this->assertInstanceOf(Reference::class, $registry->getArgument(0));

        $this->assertLocatorServices($container, $registry->getArgument(0), [
            'aFooProcessor' => 'aFooProcessor',
        ]);
    }

    public function testShouldRegisterWithCustomProcessorName()
    {
        $registry = new Definition(ProcessorRegistryInterface::class);
        $registry->addArgument(null);

        $container = new ContainerBuilder();
        $container->setParameter('enqueue.transports', ['foo']);
        $container->setParameter('enqueue.default_transport', 'foo');
        $container->setDefinition('enqueue.transport.foo.processor_registry', $registry);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', ['processor' => 'customProcessorName'])
        ;

        $pass = new BuildProcessorRegistryPass();

        $pass->process($container);

        $this->assertInstanceOf(Reference::class, $registry->getArgument(0));

        $this->assertLocatorServices($container, $registry->getArgument(0), [
            'customProcessorName' => 'aFooProcessor',
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
