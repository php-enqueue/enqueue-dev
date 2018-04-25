<?php

namespace Enqueue\Fs\Tests\Symfony;

use Enqueue\Fs\Client\FsDriver;
use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Fs\Symfony\FsTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FsTransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, FsTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new FsTransportFactory();

        $this->assertEquals('fs', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new FsTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfiguration()
    {
        $transport = new FsTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [[
            'path' => sys_get_temp_dir(),
        ]]);

        $this->assertEquals([
            'path' => sys_get_temp_dir(),
            'pre_fetch_count' => 1,
            'chmod' => 0600,
            'polling_interval' => 100,
        ], $config);
    }

    public function testShouldAllowAddConfigurationAsString()
    {
        $transport = new FsTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['fileDSN']);

        $this->assertEquals([
            'dsn' => 'fileDSN',
            'pre_fetch_count' => 1,
            'chmod' => 0600,
            'polling_interval' => 100,
        ], $config);
    }

    public function testShouldCreateConnectionFactory()
    {
        $container = new ContainerBuilder();

        $transport = new FsTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'path' => sys_get_temp_dir(),
            'pre_fetch_count' => 1,
            'chmod' => 0600,
            'polling_interval' => 100,
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(FsConnectionFactory::class, $factory->getClass());
        $this->assertSame([[
            'path' => sys_get_temp_dir(),
            'pre_fetch_count' => 1,
            'chmod' => 0600,
            'polling_interval' => 100,
        ]], $factory->getArguments());
    }

    public function testShouldCreateConnectionFactoryFromDsnString()
    {
        $container = new ContainerBuilder();

        $transport = new FsTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, [
            'dsn' => 'theFileDSN',
        ]);

        $this->assertTrue($container->hasDefinition($serviceId));
        $factory = $container->getDefinition($serviceId);
        $this->assertEquals(FsConnectionFactory::class, $factory->getClass());
        $this->assertSame(['theFileDSN'], $factory->getArguments());
    }

    public function testShouldCreateContext()
    {
        $container = new ContainerBuilder();

        $transport = new FsTransportFactory();

        $serviceId = $transport->createContext($container, [
            'path' => sys_get_temp_dir(),
            'pre_fetch_count' => 1,
            'chmod' => 0600,
            'polling_interval' => 100,
        ]);

        $this->assertEquals('enqueue.transport.fs.context', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $context = $container->getDefinition('enqueue.transport.fs.context');
        $this->assertInstanceOf(Reference::class, $context->getFactory()[0]);
        $this->assertEquals('enqueue.transport.fs.connection_factory', (string) $context->getFactory()[0]);
        $this->assertEquals('createContext', $context->getFactory()[1]);
    }

    public function testShouldCreateDriver()
    {
        $container = new ContainerBuilder();

        $transport = new FsTransportFactory();

        $serviceId = $transport->createDriver($container, []);

        $this->assertEquals('enqueue.client.fs.driver', $serviceId);
        $this->assertTrue($container->hasDefinition($serviceId));

        $driver = $container->getDefinition($serviceId);
        $this->assertSame(FsDriver::class, $driver->getClass());

        $this->assertInstanceOf(Reference::class, $driver->getArgument(0));
        $this->assertEquals('enqueue.transport.fs.context', (string) $driver->getArgument(0));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(1));
        $this->assertEquals('enqueue.client.config', (string) $driver->getArgument(1));

        $this->assertInstanceOf(Reference::class, $driver->getArgument(2));
        $this->assertEquals('enqueue.client.meta.queue_meta_registry', (string) $driver->getArgument(2));
    }
}
