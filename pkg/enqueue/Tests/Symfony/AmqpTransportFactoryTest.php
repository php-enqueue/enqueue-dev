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
        $transport = new AmqpTransportFactory();

        $this->assertEquals('amqp', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new AmqpTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testThrowIfCouldBeConstructedWithCustomName()
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
        $transport = new AmqpTransportFactory();
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

    public function testShouldAllowAddSslOptions()
    {
        $transport = new AmqpTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [[
            'ssl_on' => true,
            'ssl_verify' => false,
            'ssl_cacert' => '/path/to/cacert.pem',
            'ssl_cert' => '/path/to/cert.pem',
            'ssl_key' => '/path/to/key.pem',
        ]]);

        $this->assertEquals([
            'ssl_on' => true,
            'ssl_verify' => false,
            'ssl_cacert' => '/path/to/cacert.pem',
            'ssl_cert' => '/path/to/cert.pem',
            'ssl_key' => '/path/to/key.pem',
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
        ], $config);
    }

    public function testThrowIfInvalidReceiveMethodIsSet()
    {
        $transport = new AmqpTransportFactory();
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
        $transport = new AmqpTransportFactory();
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

        $transport = new AmqpTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, []);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(AmqpConnectionFactory::class, $factory->getClass());

        $this->assertSame([[]], $factory->getArguments());
    }

    public function testShouldCreateConnectionFactoryFromDsnString()
    {
        $container = new ContainerBuilder();

        $transport = new AmqpTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'dsn' => 'theConnectionDSN:',
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(AmqpConnectionFactory::class, $factory->getClass());
        $this->assertSame([['dsn' => 'theConnectionDSN:']], $factory->getArguments());
    }

    public function testShouldCreateConnectionFactoryAndMergeDriverOptionsIfSet()
    {
        $container = new ContainerBuilder();

        $transport = new AmqpTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'host' => 'aHost',
            'driver_options' => [
                'foo' => 'fooVal',
            ],
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(AmqpConnectionFactory::class, $factory->getClass());
        $this->assertSame([['foo' => 'fooVal', 'host' => 'aHost']], $factory->getArguments());
    }

    public function testShouldCreateConnectionFactoryFromDsnStringPlushArrayOptions()
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

    public function testShouldCreateAmqpExtConnectionFactoryBySetDriver()
    {
        $factory = AmqpTransportFactory::createConnectionFactoryFactory(['driver' => 'ext']);

        $this->assertInstanceOf(\Enqueue\AmqpExt\AmqpConnectionFactory::class, $factory);
    }

    public function testShouldCreateAmqpLibConnectionFactoryBySetDriver()
    {
        $factory = AmqpTransportFactory::createConnectionFactoryFactory(['driver' => 'lib']);

        $this->assertInstanceOf(\Enqueue\AmqpLib\AmqpConnectionFactory::class, $factory);
    }

    public function testShouldCreateAmqpBunnyConnectionFactoryBySetDriver()
    {
        $factory = AmqpTransportFactory::createConnectionFactoryFactory(['driver' => 'bunny']);

        $this->assertInstanceOf(\Enqueue\AmqpBunny\AmqpConnectionFactory::class, $factory);
    }

    public function testShouldCreateAmqpExtFromConfigWithoutDriverAndDsn()
    {
        $factory = AmqpTransportFactory::createConnectionFactoryFactory(['host' => 'aHost']);

        $this->assertInstanceOf(\Enqueue\AmqpExt\AmqpConnectionFactory::class, $factory);
    }

    public function testThrowIfInvalidDriverGiven()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unexpected driver given "invalidDriver"');

        AmqpTransportFactory::createConnectionFactoryFactory(['driver' => 'invalidDriver']);
    }

    public function testShouldCreateAmqpExtFromDsn()
    {
        $factory = AmqpTransportFactory::createConnectionFactoryFactory(['dsn' => 'amqp:']);

        $this->assertInstanceOf(\Enqueue\AmqpExt\AmqpConnectionFactory::class, $factory);
    }

    public function testShouldCreateAmqpBunnyFromDsnWithDriver()
    {
        $factory = AmqpTransportFactory::createConnectionFactoryFactory(['dsn' => 'amqp+bunny:']);

        $this->assertInstanceOf(\Enqueue\AmqpBunny\AmqpConnectionFactory::class, $factory);
    }

    public function testThrowIfNotAmqpDsnProvided()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Factory must be instance of "Interop\Amqp\AmqpConnectionFactory" but got "Enqueue\Sqs\SqsConnectionFactory"');

        AmqpTransportFactory::createConnectionFactoryFactory(['dsn' => 'sqs:']);
    }
}
