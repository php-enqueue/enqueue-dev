<?php

namespace Enqueue\Mongodb\Tests\Symfony;

use Enqueue\Mongodb\Client\MongodbDriver;
use Enqueue\Mongodb\MongodbConnectionFactory;
use Enqueue\Mongodb\Symfony\MongodbTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @group mongodb
 */
class MongodbTransportFactoryTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, MongodbTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new MongodbTransportFactory();

        $this->assertEquals('mongodb', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new MongodbTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfiguration()
    {
        $transport = new MongodbTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [[
            'dsn' => 'mongodb://127.0.0.1/',
        ]]);

        $this->assertEquals([
            'dsn' => 'mongodb://127.0.0.1/',
            'dbname' => 'enqueue',
            'collection_name' => 'enqueue',
            'polling_interval' => 1000,
        ], $config);
    }

    public function testShouldAllowAddConfigurationAsString()
    {
        $transport = new MongodbTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['mysqlDSN']);

        $this->assertEquals([
            'dsn' => 'mysqlDSN',
            'dbname' => 'enqueue',
            'collection_name' => 'enqueue',
            'polling_interval' => 1000,
        ], $config);
    }

    public function testShouldCreateMongodbConnectionFactory()
    {
        $container = new ContainerBuilder();

        $transport = new MongodbTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'dsn' => 'mysqlDSN',
            'dbname' => 'enqueue',
            'collection_name' => 'enqueue',
            'polling_interval' => 1000,
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(MongodbConnectionFactory::class, $factory->getClass());

        $this->assertSame([
            'dsn' => 'mysqlDSN',
            'dbname' => 'enqueue',
            'collection_name' => 'enqueue',
            'polling_interval' => 1000,
        ], $factory->getArgument(0));
    }

    public function testShouldCreateConnectionFactoryFromDsnString()
    {
        $container = new ContainerBuilder();

        $transport = new MongodbTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'dsn' => 'theDSN',
            'connection' => [],
            'lazy' => true,
            'table_name' => 'enqueue',
            'polling_interval' => 1000,
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(MongodbConnectionFactory::class, $factory->getClass());
        $this->assertSame('theDSN', $factory->getArgument(0)['dsn']);
    }

    public function testShouldCreateContext()
    {
        $container = new ContainerBuilder();

        $transport = new MongodbTransportFactory();

        $serviceId = $transport->createContext($container, []);

        $this->assertEquals('enqueue.transport.mongodb.context', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $context = $container->getDefinition('enqueue.transport.mongodb.context');
        $this->assertInstanceOf(Reference::class, $context->getFactory()[0]);
        $this->assertEquals('enqueue.transport.mongodb.connection_factory', (string) $context->getFactory()[0]);
        $this->assertEquals('createContext', $context->getFactory()[1]);
    }

    public function testShouldCreateDriver()
    {
        $container = new ContainerBuilder();

        $transport = new MongodbTransportFactory();

        $serviceId = $transport->createDriver($container, []);

        $this->assertEquals('enqueue.client.mongodb.driver', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $driver = $container->getDefinition($serviceId);
        $this->assertSame(MongodbDriver::class, $driver->getClass());

        $this->assertInstanceOf(Reference::class, $driver->getArgument(0));
        $this->assertEquals('enqueue.transport.mongodb.context', (string) $driver->getArgument(0));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(1));
        $this->assertEquals('enqueue.client.config', (string) $driver->getArgument(1));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(2));
        $this->assertEquals('enqueue.client.meta.queue_meta_registry', (string) $driver->getArgument(2));
    }
}
