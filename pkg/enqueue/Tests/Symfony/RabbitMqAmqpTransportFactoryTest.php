<?php

namespace Enqueue\Tests\Symfony;

use Enqueue\Client\Amqp\RabbitMqDriver;
use Enqueue\Symfony\AmqpTransportFactory;
use Enqueue\Symfony\RabbitMqAmqpTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Amqp\AmqpConnectionFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RabbitMqAmqpTransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, RabbitMqAmqpTransportFactory::class);
    }

    public function testShouldExtendAmqpTransportFactoryClass()
    {
        $this->assertClassExtends(AmqpTransportFactory::class, RabbitMqAmqpTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new RabbitMqAmqpTransportFactory();

        $this->assertEquals('rabbitmq_amqp', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new RabbitMqAmqpTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfiguration()
    {
        $transport = new RabbitMqAmqpTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), []);

        $this->assertEquals([
            'delay_strategy' => 'dlx',
        ], $config);
    }

    public function testShouldCreateConnectionFactory()
    {
        $container = new ContainerBuilder();

        $transport = new RabbitMqAmqpTransportFactory();

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

        $transport = new RabbitMqAmqpTransportFactory();

        $serviceId = $transport->createContext($container, [
            'host' => 'localhost',
            'port' => 5672,
            'user' => 'guest',
            'pass' => 'guest',
            'vhost' => '/',
            'persisted' => false,
            'delay_strategy' => null,
        ]);

        $this->assertEquals('enqueue.transport.rabbitmq_amqp.context', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $context = $container->getDefinition('enqueue.transport.rabbitmq_amqp.context');
        $this->assertInstanceOf(Reference::class, $context->getFactory()[0]);
        $this->assertEquals('enqueue.transport.rabbitmq_amqp.connection_factory', (string) $context->getFactory()[0]);
        $this->assertEquals('createContext', $context->getFactory()[1]);
    }

    public function testShouldCreateDriver()
    {
        $container = new ContainerBuilder();

        $transport = new RabbitMqAmqpTransportFactory();

        $serviceId = $transport->createDriver($container, []);

        $this->assertEquals('enqueue.client.rabbitmq_amqp.driver', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $driver = $container->getDefinition($serviceId);
        $this->assertSame(RabbitMqDriver::class, $driver->getClass());
    }
}
