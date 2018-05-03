<?php

namespace Enqueue\RdKafka\Tests\Symfony;

use Enqueue\RdKafka\Client\RdKafkaDriver;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\RdKafka\Symfony\RdKafkaTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * @group rdkafka
 */
class RdKafkaTransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, RdKafkaTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new RdKafkaTransportFactory();

        $this->assertEquals('rdkafka', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new RdKafkaTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfiguration()
    {
        $transport = new RdKafkaTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [[
        ]]);

        $this->assertEquals([
            'topic' => [],
            'commit_async' => false,
            'global' => [],
        ], $config);
    }

    public function testShouldAllowAddConfigurationAsString()
    {
        $transport = new RdKafkaTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['kafkaDSN']);

        $this->assertEquals([
            'dsn' => 'kafkaDSN',
            'topic' => [],
            'commit_async' => false,
            'global' => [],
        ], $config);
    }

    public function testShouldCreateConnectionFactory()
    {
        $container = new ContainerBuilder();

        $transport = new RdKafkaTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(RdKafkaConnectionFactory::class, $factory->getClass());
        $this->assertSame([[
        ]], $factory->getArguments());
    }

    public function testShouldCreateConnectionFactoryFromDsnString()
    {
        $container = new ContainerBuilder();

        $transport = new RdKafkaTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'dsn' => 'theKafkaDSN',
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(RdKafkaConnectionFactory::class, $factory->getClass());
        $this->assertSame(['theKafkaDSN'], $factory->getArguments());
    }

    public function testShouldCreateContext()
    {
        $container = new ContainerBuilder();

        $transport = new RdKafkaTransportFactory();

        $serviceId = $transport->createContext($container, [
        ]);

        $this->assertEquals('enqueue.transport.rdkafka.context', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $context = $container->getDefinition('enqueue.transport.rdkafka.context');
        $this->assertInstanceOf(Reference::class, $context->getFactory()[0]);
        $this->assertEquals('enqueue.transport.rdkafka.connection_factory', (string) $context->getFactory()[0]);
        $this->assertEquals('createContext', $context->getFactory()[1]);
    }

    public function testShouldCreateDriver()
    {
        $container = new ContainerBuilder();

        $transport = new RdKafkaTransportFactory();

        $serviceId = $transport->createDriver($container, []);

        $this->assertEquals('enqueue.client.rdkafka.driver', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $driver = $container->getDefinition($serviceId);
        $this->assertSame(RdKafkaDriver::class, $driver->getClass());

        $this->assertInstanceOf(Reference::class, $driver->getArgument(0));
        $this->assertEquals('enqueue.transport.rdkafka.context', (string) $driver->getArgument(0));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(1));
        $this->assertEquals('enqueue.client.config', (string) $driver->getArgument(1));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(2));
        $this->assertEquals('enqueue.client.meta.queue_meta_registry', (string) $driver->getArgument(2));
    }
}
