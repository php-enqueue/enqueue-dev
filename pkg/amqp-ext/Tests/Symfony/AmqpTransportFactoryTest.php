<?php

namespace Enqueue\AmqpExt\Tests\Symfony;

use Enqueue\AmqpExt\AmqpConnectionFactory;
use Enqueue\AmqpExt\Client\AmqpDriver;
use Enqueue\AmqpExt\Symfony\AmqpTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use PHPUnit\Framework\TestCase;

class AmqpTransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, AmqpTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new AmqpTransportFactory();

        $this->assertEquals('amqp', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new AmqpTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfiguration()
    {
        $transport = new AmqpTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), []);

        $this->assertEquals([
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'persisted' => false,
            'lazy' => true,
        ], $config);
    }

    public function testShouldAllowAddConfigurationAsString()
    {
        $transport = new AmqpTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['amqpDSN']);

        $this->assertEquals([
            'dsn' => 'amqpDSN',
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'persisted' => false,
            'lazy' => true,
        ], $config);
    }

    public function testShouldCreateConnectionFactory()
    {
        $container = new ContainerBuilder();

        $transport = new AmqpTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'persisted' => false,
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(AmqpConnectionFactory::class, $factory->getClass());
        $this->assertSame([[
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'persisted' => false,
        ]], $factory->getArguments());
    }

    public function testShouldCreateConnectionFactoryFromDsnString()
    {
        $container = new ContainerBuilder();

        $transport = new AmqpTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'dsn' => 'theConnectionDSN',
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'persisted' => false,
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(AmqpConnectionFactory::class, $factory->getClass());
        $this->assertSame(['theConnectionDSN'], $factory->getArguments());
    }

    public function testShouldCreateContext()
    {
        $container = new ContainerBuilder();

        $transport = new AmqpTransportFactory();

        $serviceId = $transport->createContext($container, [
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'persisted' => false,
        ]);

        $this->assertEquals('enqueue.transport.amqp.context', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $context = $container->getDefinition('enqueue.transport.amqp.context');
        $this->assertInstanceOf(Reference::class, $context->getFactory()[0]);
        $this->assertEquals('enqueue.transport.amqp.connection_factory', (string) $context->getFactory()[0]);
        $this->assertEquals('createContext', $context->getFactory()[1]);
    }

    public function testShouldCreateDriver()
    {
        $container = new ContainerBuilder();

        $transport = new AmqpTransportFactory();

        $serviceId = $transport->createDriver($container, []);

        $this->assertEquals('enqueue.client.amqp.driver', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $driver = $container->getDefinition($serviceId);
        $this->assertSame(AmqpDriver::class, $driver->getClass());

        $this->assertInstanceOf(Reference::class, $driver->getArgument(0));
        $this->assertEquals('enqueue.transport.amqp.context', (string) $driver->getArgument(0));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(1));
        $this->assertEquals('enqueue.client.config', (string) $driver->getArgument(1));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(2));
        $this->assertEquals('enqueue.client.meta.queue_meta_registry', (string) $driver->getArgument(2));
    }
}
