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

class AmqpTransportFactoryTest extends \PHPUnit_Framework_TestCase
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
            'login' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
            'persisted' => false,
        ], $config);
    }

    public function testShouldCreateContext()
    {
        $container = new ContainerBuilder();

        $transport = new AmqpTransportFactory();

        $serviceId = $transport->createContext($container, [
            'host' => 'localhost',
            'port' => 5672,
            'login' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
            'persisted' => false,
        ]);

        $this->assertEquals('enqueue.transport.amqp.context', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $context = $container->getDefinition('enqueue.transport.amqp.context');
        $this->assertInstanceOf(Reference::class, $context->getFactory()[0]);
        $this->assertEquals('enqueue.transport.amqp.connection_factory', (string) $context->getFactory()[0]);
        $this->assertEquals('createContext', $context->getFactory()[1]);

        $this->assertTrue($container->hasDefinition('enqueue.transport.amqp.connection_factory'));
        $factory = $container->getDefinition('enqueue.transport.amqp.connection_factory');
        $this->assertEquals(AmqpConnectionFactory::class, $factory->getClass());
        $this->assertSame([[
            'host' => 'localhost',
            'port' => 5672,
            'login' => 'guest',
            'password' => 'guest',
            'vhost' => '/',
            'persisted' => false,
        ]], $factory->getArguments());
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
    }
}
