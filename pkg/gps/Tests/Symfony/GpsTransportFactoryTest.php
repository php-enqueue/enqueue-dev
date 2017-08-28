<?php

namespace Enqueue\Gps\Tests\Symfony;

use Enqueue\Gps\Client\GpsDriver;
use Enqueue\Gps\GpsConnectionFactory;
use Enqueue\Gps\Symfony\GpsTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class GpsTransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, GpsTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new GpsTransportFactory();

        $this->assertEquals('gps', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new GpsTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfiguration()
    {
        $transport = new GpsTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), []);

        $this->assertEquals([
            'retries' => 3,
            'scopes' => [],
            'lazy' => true,
        ], $config);
    }

    public function testShouldAllowAddConfigurationAsString()
    {
        $transport = new GpsTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['gpsDSN']);

        $this->assertEquals([
            'dsn' => 'gpsDSN',
            'lazy' => true,
            'retries' => 3,
            'scopes' => [],
        ], $config);
    }

    public function testShouldCreateConnectionFactory()
    {
        $container = new ContainerBuilder();

        $transport = new GpsTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'projectId' => null,
            'lazy' => false,
            'retries' => 3,
            'scopes' => [],
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(GpsConnectionFactory::class, $factory->getClass());
        $this->assertSame([[
            'lazy' => false,
            'retries' => 3,
        ]], $factory->getArguments());
    }

    public function testShouldCreateConnectionFactoryFromDsnString()
    {
        $container = new ContainerBuilder();

        $transport = new GpsTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'dsn' => 'theConnectionDSN',
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(GpsConnectionFactory::class, $factory->getClass());
        $this->assertSame(['theConnectionDSN'], $factory->getArguments());
    }

    public function testShouldCreateContext()
    {
        $container = new ContainerBuilder();

        $transport = new GpsTransportFactory();

        $serviceId = $transport->createContext($container, [
            'projectId' => null,
            'lazy' => false,
            'retries' => 3,
            'scopes' => [],
        ]);

        $this->assertEquals('enqueue.transport.gps.context', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $context = $container->getDefinition('enqueue.transport.gps.context');
        $this->assertInstanceOf(Reference::class, $context->getFactory()[0]);
        $this->assertEquals('enqueue.transport.gps.connection_factory', (string) $context->getFactory()[0]);
        $this->assertEquals('createContext', $context->getFactory()[1]);
    }

    public function testShouldCreateDriver()
    {
        $container = new ContainerBuilder();

        $transport = new GpsTransportFactory();

        $serviceId = $transport->createDriver($container, []);

        $this->assertEquals('enqueue.client.gps.driver', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $driver = $container->getDefinition($serviceId);
        $this->assertSame(GpsDriver::class, $driver->getClass());

        $this->assertInstanceOf(Reference::class, $driver->getArgument(0));
        $this->assertEquals('enqueue.transport.gps.context', (string) $driver->getArgument(0));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(1));
        $this->assertEquals('enqueue.client.config', (string) $driver->getArgument(1));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(2));
        $this->assertEquals('enqueue.client.meta.queue_meta_registry', (string) $driver->getArgument(2));
    }
}
