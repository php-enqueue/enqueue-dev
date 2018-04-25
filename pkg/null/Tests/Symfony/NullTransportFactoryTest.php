<?php

namespace Enqueue\Null\Tests\Symfony;

use Enqueue\Null\Client\NullDriver;
use Enqueue\Null\NullConnectionFactory;
use Enqueue\Null\NullContext;
use Enqueue\Null\Symfony\NullTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class NullTransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, NullTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new NullTransportFactory();

        $this->assertEquals('null', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new NullTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfiguration()
    {
        $transport = new NullTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [true]);

        $this->assertEquals([], $config);
    }

    public function testShouldAllowAddConfigurationWithDsnString()
    {
        $transport = new NullTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['aStringDSN']);

        $this->assertEquals(['dsn' => 'aStringDSN'], $config);
    }

    public function testShouldAllowAddConfigurationWithDsnStringOption()
    {
        $transport = new NullTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [['dsn' => 'aStringDSN']]);

        $this->assertEquals(['dsn' => 'aStringDSN'], $config);
    }

    public function testShouldCreateConnectionFactory()
    {
        $container = new ContainerBuilder();

        $transport = new NullTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, []);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(NullConnectionFactory::class, $factory->getClass());
        $this->assertSame([], $factory->getArguments());
    }

    public function testShouldCreateContext()
    {
        $container = new ContainerBuilder();

        $transport = new NullTransportFactory();

        $serviceId = $transport->createContext($container, []);

        $this->assertEquals('enqueue.transport.null.context', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $context = $container->getDefinition($serviceId);
        $this->assertEquals(NullContext::class, $context->getClass());
        $this->assertEquals(
            [new Reference('enqueue.transport.null.connection_factory'), 'createContext'],
            $context->getFactory()
        );
    }

    public function testShouldCreateDriver()
    {
        $container = new ContainerBuilder();

        $transport = new NullTransportFactory();

        $driverId = $transport->createDriver($container, []);

        $this->assertEquals('enqueue.client.null.driver', $driverId);
        $this->assertTrue($container->hasDefinition($driverId));

        $driver = $container->getDefinition($driverId);
        $this->assertEquals(NullDriver::class, $driver->getClass());
        $this->assertNull($driver->getFactory());

        $this->assertInstanceOf(Reference::class, $driver->getArgument(0));
        $this->assertEquals('enqueue.transport.null.context', (string) $driver->getArgument(0));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(1));
        $this->assertEquals('enqueue.client.config', (string) $driver->getArgument(1));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(2));
        $this->assertEquals('enqueue.client.meta.queue_meta_registry', (string) $driver->getArgument(2));
    }
}
