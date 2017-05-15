<?php

namespace Enqueue\Sqs\Tests\Symfony;

use Enqueue\Sqs\Client\SqsDriver;
use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Sqs\Symfony\SqsTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class SqsTransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, SqsTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new SqsTransportFactory();

        $this->assertEquals('sqs', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new SqsTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfiguration()
    {
        $transport = new SqsTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [[
            'key' => 'theKey',
            'secret' => 'theSecret',
            'token' => 'theToken',
            'region' => 'theRegion',
            'retries' => 5,
            'version' => 'theVersion',
            'lazy' => false,
        ]]);

        $this->assertEquals([
            'key' => 'theKey',
            'secret' => 'theSecret',
            'token' => 'theToken',
            'region' => 'theRegion',
            'retries' => 5,
            'version' => 'theVersion',
            'lazy' => false,
        ], $config);
    }

    public function testShouldCreateConnectionFactory()
    {
        $container = new ContainerBuilder();

        $transport = new SqsTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'key' => 'theKey',
            'secret' => 'theSecret',
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(SqsConnectionFactory::class, $factory->getClass());
        $this->assertSame([[
            'key' => 'theKey',
            'secret' => 'theSecret',
        ]], $factory->getArguments());
    }

    public function testShouldCreateContext()
    {
        $container = new ContainerBuilder();

        $transport = new SqsTransportFactory();

        $serviceId = $transport->createContext($container, [
            'key' => 'theKey',
            'secret' => 'theSecret',
        ]);

        $this->assertEquals('enqueue.transport.sqs.context', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $context = $container->getDefinition('enqueue.transport.sqs.context');
        $this->assertInstanceOf(Reference::class, $context->getFactory()[0]);
        $this->assertEquals('enqueue.transport.sqs.connection_factory', (string) $context->getFactory()[0]);
        $this->assertEquals('createContext', $context->getFactory()[1]);
    }

    public function testShouldCreateDriver()
    {
        $container = new ContainerBuilder();

        $transport = new SqsTransportFactory();

        $serviceId = $transport->createDriver($container, []);

        $this->assertEquals('enqueue.client.sqs.driver', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $driver = $container->getDefinition($serviceId);
        $this->assertSame(SqsDriver::class, $driver->getClass());

        $this->assertInstanceOf(Reference::class, $driver->getArgument(0));
        $this->assertEquals('enqueue.transport.sqs.context', (string) $driver->getArgument(0));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(1));
        $this->assertEquals('enqueue.client.config', (string) $driver->getArgument(1));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(2));
        $this->assertEquals('enqueue.client.meta.queue_meta_registry', (string) $driver->getArgument(2));
    }
}
