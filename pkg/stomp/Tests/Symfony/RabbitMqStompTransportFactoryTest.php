<?php

namespace Enqueue\Stomp\Tests\Symfony;

use Enqueue\Stomp\Client\ManagementClient;
use Enqueue\Stomp\Client\RabbitMqStompDriver;
use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Stomp\Symfony\RabbitMqStompTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RabbitMqStompTransportFactoryTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, RabbitMqStompTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new RabbitMqStompTransportFactory();

        $this->assertEquals('rabbitmq_stomp', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new RabbitMqStompTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfiguration()
    {
        $transport = new RabbitMqStompTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), []);

        $this->assertEquals([
            'host' => 'localhost',
            'port' => 61613,
            'login' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
            'sync' => true,
            'connection_timeout' => 1,
            'buffer_size' => 1000,
            'delay_plugin_installed' => false,
            'management_plugin_installed' => false,
            'management_plugin_port' => 15672,
            'lazy' => true,
            'ssl_on' => false,
        ], $config);
    }

    public function testShouldCreateConnectionFactory()
    {
        $container = new ContainerBuilder();

        $transport = new RabbitMqStompTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'uri' => 'tcp://localhost:61613',
            'login' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
            'sync' => true,
            'connection_timeout' => 1,
            'buffer_size' => 1000,
            'delay_plugin_installed' => false,
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(StompConnectionFactory::class, $factory->getClass());
        $this->assertSame([[
            'uri' => 'tcp://localhost:61613',
            'login' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
            'sync' => true,
            'connection_timeout' => 1,
            'buffer_size' => 1000,
            'delay_plugin_installed' => false,
        ]], $factory->getArguments());
    }

    public function testShouldCreateContext()
    {
        $container = new ContainerBuilder();

        $transport = new RabbitMqStompTransportFactory();

        $serviceId = $transport->createContext($container, [
            'uri' => 'tcp://localhost:61613',
            'login' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
            'sync' => true,
            'connection_timeout' => 1,
            'buffer_size' => 1000,
            'delay_plugin_installed' => false,
        ]);

        $this->assertEquals('enqueue.transport.rabbitmq_stomp.context', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $context = $container->getDefinition('enqueue.transport.rabbitmq_stomp.context');
        $this->assertInstanceOf(Reference::class, $context->getFactory()[0]);
        $this->assertEquals('enqueue.transport.rabbitmq_stomp.connection_factory', (string) $context->getFactory()[0]);
        $this->assertEquals('createContext', $context->getFactory()[1]);
    }

    public function testShouldCreateDriver()
    {
        $container = new ContainerBuilder();

        $transport = new RabbitMqStompTransportFactory();

        $serviceId = $transport->createDriver($container, [
            'vhost' => 'vhost',
            'host' => 'host',
            'management_plugin_port' => 'port',
            'login' => 'login',
            'password' => 'password',
        ]);

        $this->assertTrue($container->hasDefinition('enqueue.client.rabbitmq_stomp.management_client'));
        $managementClient = $container->getDefinition('enqueue.client.rabbitmq_stomp.management_client');
        $this->assertEquals(ManagementClient::class, $managementClient->getClass());
        $this->assertEquals([ManagementClient::class, 'create'], $managementClient->getFactory());
        $this->assertEquals([
            'vhost',
            'host',
            'port',
            'login',
            'password',
        ], $managementClient->getArguments());

        $this->assertEquals('enqueue.client.rabbitmq_stomp.driver', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $driver = $container->getDefinition($serviceId);
        $this->assertSame(RabbitMqStompDriver::class, $driver->getClass());
    }
}
