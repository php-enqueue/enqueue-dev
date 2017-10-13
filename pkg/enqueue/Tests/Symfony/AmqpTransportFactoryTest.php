<?php

namespace Enqueue\Tests\Symfony;

use Enqueue\Client\Amqp\AmqpDriver;
use Enqueue\Symfony\AmqpTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Amqp\AmqpConnectionFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class AmqpTransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, AmqpTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new AmqpTransportFactory($this->createAmqpConnectionFactoryClass());

        $this->assertEquals('amqp', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new AmqpTransportFactory($this->createAmqpConnectionFactoryClass(), 'theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfiguration()
    {
        $transport = new AmqpTransportFactory($this->createAmqpConnectionFactoryClass());
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [[
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'read_timeout' => 3.,
            'write_timeout' => 3.,
            'connection_timeout' => 3.,
            'heartbeat' => 0,
            'persisted' => false,
            'lazy' => true,
            'qos_global' => false,
            'qos_prefetch_size' => 0,
            'qos_prefetch_count' => 1,
            'receive_method' => 'basic_get',
        ]]);

        $this->assertEquals([
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'read_timeout' => 3.,
            'write_timeout' => 3.,
            'connection_timeout' => 3.,
            'heartbeat' => 0,
            'persisted' => false,
            'lazy' => true,
            'qos_global' => false,
            'qos_prefetch_size' => 0,
            'qos_prefetch_count' => 1,
            'receive_method' => 'basic_get',
        ], $config);
    }

    public function testShouldAllowAddConfigurationWithDriverOptions()
    {
        $transport = new AmqpTransportFactory($this->createAmqpConnectionFactoryClass());
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [[
            'host' => 'localhost',
            'driver_options' => [
                'foo' => 'fooVal',
            ],
        ]]);

        $this->assertEquals([
            'host' => 'localhost',
            'driver_options' => [
                'foo' => 'fooVal',
            ],
        ], $config);
    }

    public function testShouldAllowAddConfigurationAsString()
    {
        $transport = new AmqpTransportFactory($this->createAmqpConnectionFactoryClass());
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['amqpDSN']);

        $this->assertEquals([
            'dsn' => 'amqpDSN',
        ], $config);
    }

    public function testThrowIfInvalidReceiveMethodIsSet()
    {
        $transport = new AmqpTransportFactory($this->createAmqpConnectionFactoryClass());
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The value "anInvalidMethod" is not allowed for path "foo.receive_method". Permissible values: "basic_get", "basic_consume"');
        $processor->process($tb->buildTree(), [[
            'receive_method' => 'anInvalidMethod',
        ]]);
    }

    public function testShouldAllowChangeReceiveMethod()
    {
        $transport = new AmqpTransportFactory($this->createAmqpConnectionFactoryClass());
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [[
            'receive_method' => 'basic_consume',
        ]]);

        $this->assertEquals([
            'receive_method' => 'basic_consume',
        ], $config);
    }

    public function testShouldCreateConnectionFactoryForEmptyConfig()
    {
        $container = new ContainerBuilder();

        $expectedClass = $this->createAmqpConnectionFactoryClass();

        $transport = new AmqpTransportFactory($expectedClass);

        $serviceId = $transport->createConnectionFactory($container, []);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals($expectedClass, $factory->getClass());
        $this->assertSame([[]], $factory->getArguments());
    }

    public function testShouldCreateConnectionFactoryFromDsnString()
    {
        $container = new ContainerBuilder();

        $expectedClass = $this->createAmqpConnectionFactoryClass();

        $transport = new AmqpTransportFactory($expectedClass);

        $serviceId = $transport->createConnectionFactory($container, [
            'dsn' => 'theConnectionDSN',
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals($expectedClass, $factory->getClass());
        $this->assertSame([['dsn' => 'theConnectionDSN']], $factory->getArguments());
    }

    public function testShouldCreateConnectionFactoryAndMergeDriverOptionsIfSet()
    {
        $container = new ContainerBuilder();

        $expectedClass = $this->createAmqpConnectionFactoryClass();

        $transport = new AmqpTransportFactory($expectedClass);

        $serviceId = $transport->createConnectionFactory($container, [
            'host' => 'aHost',
            'driver_options' => [
                'foo' => 'fooVal',
            ],
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals($expectedClass, $factory->getClass());
        $this->assertSame([['foo' => 'fooVal', 'host' => 'aHost']], $factory->getArguments());
    }

    public function testShouldCreateConnectionFactoryFromDsnStringPlushArrayOptions()
    {
        $container = new ContainerBuilder();

        $expectedClass = $this->createAmqpConnectionFactoryClass();

        $transport = new AmqpTransportFactory($expectedClass);

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
        $this->assertEquals($expectedClass, $factory->getClass());
        $this->assertSame([[
            'dsn' => 'theConnectionDSN',
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'persisted' => false,
        ]], $factory->getArguments());
    }

    public function testShouldCreateContext()
    {
        $container = new ContainerBuilder();

        $transport = new AmqpTransportFactory($this->createAmqpConnectionFactoryClass());

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

        $transport = new AmqpTransportFactory($this->createAmqpConnectionFactoryClass());

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

    /**
     * @return string
     */
    private function createAmqpConnectionFactoryClass()
    {
        return $this->getMockClass(AmqpConnectionFactory::class);
    }
}
