<?php

namespace Enqueue\AmqpLib\Tests\Symfony;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Enqueue\AmqpLib\Symfony\AmqpLibTransportFactory;
use Enqueue\AmqpLib\Symfony\RabbitMqAmqpLibTransportFactory;
use Enqueue\Client\Amqp\RabbitMqDriver;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RabbitMqAmqpLibTransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, RabbitMqAmqpLibTransportFactory::class);
    }

    public function testShouldExtendAmqpTransportFactoryClass()
    {
        $this->assertClassExtends(AmqpLibTransportFactory::class, RabbitMqAmqpLibTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new RabbitMqAmqpLibTransportFactory();

        $this->assertEquals('rabbitmq_amqp_lib', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new RabbitMqAmqpLibTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfiguration()
    {
        $transport = new RabbitMqAmqpLibTransportFactory();
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
            'delay_strategy' => null,
            'lazy' => true,
            'receive_method' => 'basic_get',
            'connection_timeout' => 3.0,
            'read_write_timeout' => 3.0,
            'read_timeout' => 3,
            'write_timeout' => 3,
            'stream' => true,
            'insist' => false,
            'keepalive' => false,
            'heartbeat' => 0,
        ], $config);
    }

    public function testShouldCreateConnectionFactory()
    {
        $container = new ContainerBuilder();

        $transport = new RabbitMqAmqpLibTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'persisted' => false,
            'delay_strategy' => null,
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
            'delay_strategy' => null,
        ]], $factory->getArguments());
    }

    public function testShouldCreateContext()
    {
        $container = new ContainerBuilder();

        $transport = new RabbitMqAmqpLibTransportFactory();

        $serviceId = $transport->createContext($container, [
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'persisted' => false,
            'delay_strategy' => null,
        ]);

        $this->assertEquals('enqueue.transport.rabbitmq_amqp_lib.context', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $context = $container->getDefinition('enqueue.transport.rabbitmq_amqp_lib.context');
        $this->assertInstanceOf(Reference::class, $context->getFactory()[0]);
        $this->assertEquals('enqueue.transport.rabbitmq_amqp_lib.connection_factory', (string) $context->getFactory()[0]);
        $this->assertEquals('createContext', $context->getFactory()[1]);
    }

    public function testShouldCreateDriver()
    {
        $container = new ContainerBuilder();

        $transport = new RabbitMqAmqpLibTransportFactory();

        $serviceId = $transport->createDriver($container, []);

        $this->assertEquals('enqueue.client.rabbitmq_amqp_lib.driver', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $driver = $container->getDefinition($serviceId);
        $this->assertSame(RabbitMqDriver::class, $driver->getClass());
    }
}
