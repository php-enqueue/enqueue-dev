<?php

namespace Enqueue\Tests\Symfony;

use Enqueue\Client\NullDriver;
use Enqueue\Symfony\NullTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Transport\Null\NullConnectionFactory;
use Enqueue\Transport\Null\NullContext;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class NullTransportFactoryTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, NullTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new NullTransportFactory();

        $this->assertEquals('null', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new NullTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfiguration()
    {
        $transport = new NullTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [true]);

        $this->assertEquals([], $config);
    }

    public function testShouldCreateConnectionFactory()
    {
        $container = new ContainerBuilder();

        $transport = new NullTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, []);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(NullConnectionFactory::class, $factory->getClass());
        $this->assertSame([], $factory->getArguments());
    }

    public function testShouldCreateContext()
    {
        $container = new ContainerBuilder();

        $transport = new NullTransportFactory();

        $serviceId = $transport->createContext($container, []);

        $this->assertEquals('enqueue.transport.null.context', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $context = $container->getDefinition($serviceId);
        $this->assertEquals(NullContext::class, $context->getClass());
        $this->assertEquals(
            [new Reference('enqueue.transport.null.connection_factory'), 'createContext'],
            $context->getFactory()
        );
    }

    public function testShouldCreateDriver()
    {
        $container = new ContainerBuilder();

        $transport = new NullTransportFactory();

        $driverId = $transport->createDriver($container, []);

        $this->assertEquals('enqueue.client.null.driver', $driverId);
        $this->assertTrue($container->hasDefinition($driverId));

        $context = $container->getDefinition($driverId);
        $this->assertEquals(NullDriver::class, $context->getClass());
        $this->assertNull($context->getFactory());
    }
}
