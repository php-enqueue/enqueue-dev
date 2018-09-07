<?php

namespace Enqueue\Tests\Symfony;

use Enqueue\Symfony\DefaultTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class DefaultTransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, DefaultTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new DefaultTransportFactory();

        $this->assertEquals('default', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new DefaultTransportFactory('theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfigurationAsAliasAsString()
    {
        $transport = new DefaultTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['the_alias']);

        $this->assertEquals(['alias' => 'the_alias'], $config);
    }

    public function testShouldAllowAddConfigurationAsAliasAsOption()
    {
        $transport = new DefaultTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [['alias' => 'the_alias']]);

        $this->assertEquals(['alias' => 'the_alias'], $config);
    }

    public function testShouldAllowAddConfigurationAsDsn()
    {
        $transport = new DefaultTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['dsn://']);

        $this->assertEquals(['dsn' => 'dsn://'], $config);
    }

    /**
     * @see https://github.com/php-enqueue/enqueue-dev/issues/356
     *
     * @group bug
     */
    public function testShouldAllowAddConfigurationAsDsnWithoutSlashes()
    {
        $transport = new DefaultTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['dsn:']);

        $this->assertEquals(['dsn' => 'dsn:'], $config);
    }

    public function testShouldSetNullTransportByDefault()
    {
        $transport = new DefaultTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $config = $processor->process($tb->buildTree(), [null]);
        $this->assertEquals(['dsn' => 'null:'], $config);

        $config = $processor->process($tb->buildTree(), ['']);
        $this->assertEquals(['dsn' => 'null:'], $config);
    }

    public function testThrowIfNeitherDsnNorAliasConfigured()
    {
        $transport = new DefaultTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Either dsn or alias option must be set');
        $processor->process($tb->buildTree(), [[]]);
    }

    public function testShouldCreateConnectionFactoryFromAlias()
    {
        $container = new ContainerBuilder();

        $transport = new DefaultTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, ['alias' => 'foo']);

        $this->assertEquals('enqueue.transport.default.connection_factory', $serviceId);

        $this->assertTrue($container->hasAlias('enqueue.transport.default.connection_factory'));
        $this->assertEquals(
            'enqueue.transport.foo.connection_factory',
            (string) $container->getAlias('enqueue.transport.default.connection_factory')
        );

        $this->assertTrue($container->hasAlias('enqueue.transport.connection_factory'));
        $this->assertEquals(
            'enqueue.transport.default.connection_factory',
            (string) $container->getAlias('enqueue.transport.connection_factory')
        );
    }

    public function testShouldCreateContextFromAlias()
    {
        $container = new ContainerBuilder();

        $transport = new DefaultTransportFactory();

        $serviceId = $transport->createContext($container, ['alias' => 'the_alias']);

        $this->assertEquals('enqueue.transport.default.context', $serviceId);

        $this->assertTrue($container->hasAlias($serviceId));
        $context = $container->getAlias($serviceId);
        $this->assertEquals('enqueue.transport.the_alias.context', (string) $context);

        $this->assertTrue($container->hasAlias('enqueue.transport.context'));
        $context = $container->getAlias('enqueue.transport.context');
        $this->assertEquals($serviceId, (string) $context);
    }

    public function testShouldCreateDriverFromAlias()
    {
        $container = new ContainerBuilder();

        $transport = new DefaultTransportFactory();

        $driverId = $transport->createDriver($container, ['alias' => 'the_alias']);

        $this->assertEquals('enqueue.client.default.driver', $driverId);

        $this->assertTrue($container->hasAlias($driverId));
        $context = $container->getAlias($driverId);
        $this->assertEquals('enqueue.client.the_alias.driver', (string) $context);

        $this->assertTrue($container->hasAlias('enqueue.client.driver'));
        $context = $container->getAlias('enqueue.client.driver');
        $this->assertEquals($driverId, (string) $context);
    }

    public function testShouldCreateConnectionFactoryFromDSN()
    {
        $container = new ContainerBuilder();

        $transport = new DefaultTransportFactory();

        $serviceId = $transport->createConnectionFactory($container, ['dsn' => 'foo://bar/baz']);

        $this->assertEquals('enqueue.transport.default.connection_factory', $serviceId);

        $this->assertTrue($container->hasDefinition('enqueue.transport.default.connection_factory'));

        $this->assertNotEmpty($container->getDefinition('enqueue.transport.default.connection_factory')->getFactory());
        $this->assertEquals(
            [new Reference('enqueue.connection_factory_factory'), 'create'],
            $container->getDefinition('enqueue.transport.default.connection_factory')->getFactory())
        ;
        $this->assertSame(
            ['foo://bar/baz'],
            $container->getDefinition('enqueue.transport.default.connection_factory')->getArguments())
        ;

        $this->assertTrue($container->hasAlias('enqueue.transport.connection_factory'));
        $this->assertEquals(
            'enqueue.transport.default.connection_factory',
            (string) $container->getAlias('enqueue.transport.connection_factory')
        );
    }

    public function testShouldCreateContextFromDsn()
    {
        $container = new ContainerBuilder();

        $transport = new DefaultTransportFactory();

        $serviceId = $transport->createContext($container, ['dsn' => 'foo://bar/baz']);

        $this->assertEquals('enqueue.transport.default.context', $serviceId);

        $this->assertNotEmpty($container->getDefinition('enqueue.transport.default.context')->getFactory());
        $this->assertEquals(
            [new Reference('enqueue.transport.default.connection_factory'), 'createContext'],
            $container->getDefinition('enqueue.transport.default.context')->getFactory())
        ;
        $this->assertSame(
            [],
            $container->getDefinition('enqueue.transport.default.context')->getArguments())
        ;

        $this->assertTrue($container->hasAlias('enqueue.transport.context'));
        $this->assertEquals(
            'enqueue.transport.default.context',
            (string) $container->getAlias('enqueue.transport.context')
        );
    }

    public function testShouldCreateDriverFromDsn()
    {
        $container = new ContainerBuilder();

        $transport = new DefaultTransportFactory();

        $serviceId = $transport->createDriver($container, ['dsn' => 'foo://bar/baz', 'foo' => 'fooVal']);

        $this->assertEquals('enqueue.client.default.driver', $serviceId);

        $this->assertTrue($container->hasDefinition('enqueue.client.default.driver'));

        $this->assertNotEmpty($container->getDefinition('enqueue.client.default.driver')->getFactory());
        $this->assertEquals(
            [new Reference('enqueue.client.driver_factory'), 'create'],
            $container->getDefinition('enqueue.client.default.driver')->getFactory())
        ;
        $this->assertEquals(
            [
                new Reference('enqueue.transport.default.connection_factory'),
                'foo://bar/baz',
                ['dsn' => 'foo://bar/baz', 'foo' => 'fooVal'],
            ],
            $container->getDefinition('enqueue.client.default.driver')->getArguments())
        ;

        $this->assertTrue($container->hasAlias('enqueue.client.driver'));
        $this->assertEquals(
            'enqueue.client.default.driver',
            (string) $container->getAlias('enqueue.client.driver')
        );
    }
}
