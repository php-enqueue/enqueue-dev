<?php

namespace Enqueue\AmqpBunny\Tests\Symfony;

use Enqueue\AmqpBunny\AmqpConnectionFactory;
use Enqueue\AmqpBunny\Symfony\AmqpBunnyTransportFactory;
use Enqueue\AmqpBunny\Symfony\RabbitMqAmqpBunnyTransportFactory;
use Enqueue\Client\Amqp\RabbitMqDriver;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RabbitMqAmqpBunnyTransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, RabbitMqAmqpBunnyTransportFactory::class);
    }

    public function testShouldExtendAmqpTransportFactoryClass()
    {
        $this->assertClassExtends(AmqpBunnyTransportFactory::class, RabbitMqAmqpBunnyTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new RabbitMqAmqpBunnyTransportFactory();

        $this->assertEquals('rabbitmq_amqp_bunny', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new RabbitMqAmqpBunnyTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfiguration()
    {
        $transport = new RabbitMqAmqpBunnyTransportFactory();
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
            'heartbeat' => 0,
        ], $config);
    }

    public function testShouldCreateConnectionFactory()
    {
        $container = new ContainerBuilder();

        $transport = new RabbitMqAmqpBunnyTransportFactory();

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

        $transport = new RabbitMqAmqpBunnyTransportFactory();

        $serviceId = $transport->createContext($container, [
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'persisted' => false,
            'delay_strategy' => null,
        ]);

        $this->assertEquals('enqueue.transport.rabbitmq_amqp_bunny.context', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $context = $container->getDefinition('enqueue.transport.rabbitmq_amqp_bunny.context');
        $this->assertInstanceOf(Reference::class, $context->getFactory()[0]);
        $this->assertEquals('enqueue.transport.rabbitmq_amqp_bunny.connection_factory', (string) $context->getFactory()[0]);
        $this->assertEquals('createContext', $context->getFactory()[1]);
    }

    public function testShouldCreateDriver()
    {
        $container = new ContainerBuilder();

        $transport = new RabbitMqAmqpBunnyTransportFactory();

        $serviceId = $transport->createDriver($container, []);

        $this->assertEquals('enqueue.client.rabbitmq_amqp_bunny.driver', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $driver = $container->getDefinition($serviceId);
        $this->assertSame(RabbitMqDriver::class, $driver->getClass());
    }
}
