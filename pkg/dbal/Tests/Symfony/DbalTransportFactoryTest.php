<?php

namespace Enqueue\Dbal\Tests\Symfony;

use Enqueue\Dbal\Client\DbalDriver;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use Enqueue\Dbal\Symfony\DbalTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DbalTransportFactoryTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, DbalTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new DbalTransportFactory();

        $this->assertEquals('dbal', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new DbalTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfiguration()
    {
        $transport = new DbalTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [[
            'connection' => [
                'key' => 'value',
            ],
        ]]);

        $this->assertEquals([
            'connection' => [
                'key' => 'value',
            ],
            'lazy' => true,
            'table_name' => 'enqueue',
            'polling_interval' => 1000,
            'dbal_connection_name' => null,
        ], $config);
    }

    public function testShouldAllowAddConfigurationAsString()
    {
        $transport = new DbalTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['mysqlDSN']);

        $this->assertEquals([
            'dsn' => 'mysqlDSN',
            'dbal_connection_name' => null,
            'table_name' => 'enqueue',
            'polling_interval' => 1000,
            'lazy' => true,
        ], $config);
    }

    public function testShouldCreateDbalConnectionFactory()
    {
        $container = new ContainerBuilder();

        $transport = new DbalTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'connection' => [
                'dbname' => 'theDbName',
            ],
            'lazy' => true,
            'table_name' => 'enqueue',
            'polling_interval' => 1000,
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(DbalConnectionFactory::class, $factory->getClass());
        $this->assertSame([
            'connection' => [
                'dbname' => 'theDbName',
            ],
            'lazy' => true,
            'table_name' => 'enqueue',
            'polling_interval' => 1000,
        ], $factory->getArgument(0));
    }

    public function testShouldCreateConnectionFactoryFromDsnString()
    {
        $container = new ContainerBuilder();

        $transport = new DbalTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'dsn' => 'theDSN',
            'connection' => [],
            'lazy' => true,
            'table_name' => 'enqueue',
            'polling_interval' => 1000,
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(DbalConnectionFactory::class, $factory->getClass());
        $this->assertSame('theDSN', $factory->getArgument(0));
    }

    public function testShouldCreateManagerRegistryConnectionFactory()
    {
        $container = new ContainerBuilder();

        $transport = new DbalTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'dbal_connection_name' => 'default',
            'lazy' => true,
            'table_name' => 'enqueue',
            'polling_interval' => 1000,
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(ManagerRegistryConnectionFactory::class, $factory->getClass());
        $this->assertInstanceOf(Reference::class, $factory->getArgument(0));
        $this->assertSame('doctrine', (string) $factory->getArgument(0));
        $this->assertSame([
            'dbal_connection_name' => 'default',
            'lazy' => true,
            'table_name' => 'enqueue',
            'polling_interval' => 1000,
        ], $factory->getArgument(1));
    }

    public function testShouldCreateContext()
    {
        $container = new ContainerBuilder();

        $transport = new DbalTransportFactory();

        $serviceId = $transport->createContext($container, []);

        $this->assertEquals('enqueue.transport.dbal.context', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $context = $container->getDefinition('enqueue.transport.dbal.context');
        $this->assertInstanceOf(Reference::class, $context->getFactory()[0]);
        $this->assertEquals('enqueue.transport.dbal.connection_factory', (string) $context->getFactory()[0]);
        $this->assertEquals('createContext', $context->getFactory()[1]);
    }

    public function testShouldCreateDriver()
    {
        $container = new ContainerBuilder();

        $transport = new DbalTransportFactory();

        $serviceId = $transport->createDriver($container, []);

        $this->assertEquals('enqueue.client.dbal.driver', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $driver = $container->getDefinition($serviceId);
        $this->assertSame(DbalDriver::class, $driver->getClass());

        $this->assertInstanceOf(Reference::class, $driver->getArgument(0));
        $this->assertEquals('enqueue.transport.dbal.context', (string) $driver->getArgument(0));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(1));
        $this->assertEquals('enqueue.client.config', (string) $driver->getArgument(1));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(2));
        $this->assertEquals('enqueue.client.meta.queue_meta_registry', (string) $driver->getArgument(2));
    }
}
