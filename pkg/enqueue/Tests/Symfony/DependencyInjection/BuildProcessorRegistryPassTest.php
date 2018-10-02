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

    public function testShouldDoNothingIfProcessorRegistryServiceIsNotRegistered()
    {
        $pass = new BuildProcessorRegistryPass('aName');
        $pass->process(new ContainerBuilder());
    }

    public function testShouldRegisterProcessorWithMatchedName()
    {
        $registry = new Definition(ProcessorRegistryInterface::class);
        $registry->addArgument([]);

        $container = new ContainerBuilder();
        $container->setDefinition('enqueue.transport.foo.processor_registry', $registry);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', ['transport' => 'foo'])
        ;
        $container->register('aProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', ['transport' => 'bar'])
        ;

        $pass = new BuildProcessorRegistryPass('foo');

        $pass->process($container);

        $this->assertInstanceOf(Reference::class, $registry->getArgument(0));

        $this->assertLocatorServices($container, $registry->getArgument(0), [
            'aFooProcessor' => 'aFooProcessor',
        ]);
    }

    public function testShouldRegisterProcessorWithoutNameToDefaultTransport()
    {
        $registry = new Definition(ProcessorRegistryInterface::class);
        $registry->addArgument(null);

        $container = new ContainerBuilder();
        $container->setDefinition('enqueue.transport.default.processor_registry', $registry);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', [])
        ;
        $container->register('aProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', ['transport' => 'bar'])
        ;

        $pass = new BuildProcessorRegistryPass('default');

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
        $container->setDefinition('enqueue.transport.default.processor_registry', $registry);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', ['transport' => 'all'])
        ;
        $container->register('aProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', ['transport' => 'bar'])
        ;

        $pass = new BuildProcessorRegistryPass('default');

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
        $container->setDefinition('enqueue.transport.default.processor_registry', $registry);
        $container->register('aFooProcessor', 'aProcessorClass')
            ->addTag('enqueue.transport.processor', ['processor' => 'customProcessorName'])
        ;

        $pass = new BuildProcessorRegistryPass('default');

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
        $this->assertRegExp('/service_locator\..*?\.enqueue\./', $locatorId);

        $match = [];
        if (false == preg_match('/(service_locator\..*?)\.enqueue\./', $locatorId, $match)) {
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
