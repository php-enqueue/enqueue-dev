<?php

namespace Enqueue\Tests\Symfony\DependencyInjection;

use Enqueue\Symfony\DependencyInjection\TransportFactory;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class TransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(TransportFactory::class);
    }

    public function testShouldAllowGetNameSetInConstructor()
    {
        $transport = new TransportFactory('aName');

        $this->assertEquals('aName', $transport->getName());
    }

    public function testThrowIfEmptyNameGivenOnConstruction()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The name could not be empty.');

        new TransportFactory('');
    }

    public function testShouldAllowAddConfigurationAsStringDsn()
    {
        $transport = new TransportFactory('default');
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
        $transport = new TransportFactory('default');
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['dsn:']);

        $this->assertEquals(['dsn' => 'dsn:'], $config);
    }

    public function testShouldSetNullTransportIfNullGiven()
    {
        $transport = new TransportFactory('default');
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $config = $processor->process($tb->buildTree(), [null]);
        $this->assertEquals(['dsn' => 'null:'], $config);
    }

    public function testShouldSetNullTransportIfEmptyStringGiven()
    {
        $transport = new TransportFactory('default');
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $config = $processor->process($tb->buildTree(), ['']);
        $this->assertEquals(['dsn' => 'null:'], $config);
    }

    public function testShouldSetNullTransportIfEmptyArrayGiven()
    {
        $transport = new TransportFactory('default');
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $config = $processor->process($tb->buildTree(), [[]]);
        $this->assertEquals(['dsn' => 'null:'], $config);
    }

    public function testThrowIfEmptyDsnGiven()
    {
        $transport = new TransportFactory('default');
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The path "foo.dsn" cannot contain an empty value, but got "".');
        $processor->process($tb->buildTree(), [['dsn' => '']]);
    }

    public function testThrowIfFactoryClassAndFactoryServiceSetAtTheSameTime()
    {
        $transport = new TransportFactory('default');
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Both options factory_class and factory_service are set. Please choose one.');
        $processor->process($tb->buildTree(), [[
            'dsn' => 'foo:',
            'factory_class' => 'aFactoryClass',
            'factory_service' => 'aFactoryService',
        ]]);
    }

    public function testShouldAllowSetFactoryClass()
    {
        $transport = new TransportFactory('default');
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $config = $processor->process($tb->buildTree(), [[
            'dsn' => 'foo:',
            'factory_class' => 'theFactoryClass',
        ]]);

        $this->assertArrayHasKey('factory_class', $config);
        $this->assertSame('theFactoryClass', $config['factory_class']);
    }

    public function testShouldAllowSetFactoryService()
    {
        $transport = new TransportFactory('default');
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $config = $processor->process($tb->buildTree(), [[
            'dsn' => 'foo:',
            'factory_service' => 'theFactoryService',
        ]]);

        $this->assertArrayHasKey('factory_service', $config);
        $this->assertSame('theFactoryService', $config['factory_service']);
    }

    public function testThrowIfExtraOptionGiven()
    {
        $transport = new TransportFactory('default');
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();

        $config = $processor->process($tb->buildTree(), [['dsn' => 'foo:', 'extraOption' => 'aVal']]);
        $this->assertEquals(
            ['dsn' => 'foo:', 'extraOption' => 'aVal'],
            $config
        );
    }

    public function testShouldCreateConnectionFactoryFromDSN()
    {
        $container = new ContainerBuilder();

        $transport = new TransportFactory('default');

        $serviceId = $transport->createConnectionFactory($container, ['dsn' => 'foo://bar/baz']);

        $this->assertEquals('enqueue.transport.default.connection_factory', $serviceId);

        $this->assertTrue($container->hasDefinition('enqueue.transport.default.connection_factory'));

        $this->assertNotEmpty($container->getDefinition('enqueue.transport.default.connection_factory')->getFactory());
        $this->assertEquals(
            [new Reference('enqueue.connection_factory_factory'), 'create'],
            $container->getDefinition('enqueue.transport.default.connection_factory')->getFactory())
        ;
        $this->assertSame(
            [['dsn' => 'foo://bar/baz']],
            $container->getDefinition('enqueue.transport.default.connection_factory')->getArguments())
        ;

        $this->assertTrue($container->hasAlias('enqueue.transport.connection_factory'));
        $this->assertEquals(
            'enqueue.transport.default.connection_factory',
            (string) $container->getAlias('enqueue.transport.connection_factory')
        );
    }

    public function testShouldCreateConnectionFactoryUsingCustomFactortyClass()
    {
        $container = new ContainerBuilder();

        $transport = new TransportFactory('default');

        $serviceId = $transport->createConnectionFactory($container, ['dsn' => 'foo:', 'factory_class' => 'theFactoryClass']);

        $this->assertEquals('enqueue.transport.default.connection_factory', $serviceId);

        $this->assertTrue($container->hasDefinition('enqueue.transport.default.connection_factory_factory'));
        $this->assertSame(
            'theFactoryClass',
            $container->getDefinition('enqueue.transport.default.connection_factory_factory')->getClass()
        );

        $this->assertTrue($container->hasDefinition('enqueue.transport.default.connection_factory'));

        $this->assertNotEmpty($container->getDefinition('enqueue.transport.default.connection_factory')->getFactory());
        $this->assertEquals(
            [new Reference('enqueue.transport.default.connection_factory_factory'), 'create'],
            $container->getDefinition('enqueue.transport.default.connection_factory')->getFactory())
        ;
        $this->assertSame(
            [['dsn' => 'foo:']],
            $container->getDefinition('enqueue.transport.default.connection_factory')->getArguments())
        ;

        $this->assertTrue($container->hasAlias('enqueue.transport.connection_factory'));
        $this->assertEquals(
            'enqueue.transport.default.connection_factory',
            (string) $container->getAlias('enqueue.transport.connection_factory')
        );
    }

    public function testShouldCreateConnectionFactoryUsingCustomFactortyService()
    {
        $container = new ContainerBuilder();

        $transport = new TransportFactory('default');

        $serviceId = $transport->createConnectionFactory($container, ['dsn' => 'foo:', 'factory_service' => 'theFactoryService']);

        $this->assertEquals('enqueue.transport.default.connection_factory', $serviceId);

        $this->assertTrue($container->hasDefinition('enqueue.transport.default.connection_factory'));

        $this->assertNotEmpty($container->getDefinition('enqueue.transport.default.connection_factory')->getFactory());
        $this->assertEquals(
            [new Reference('theFactoryService'), 'create'],
            $container->getDefinition('enqueue.transport.default.connection_factory')->getFactory())
        ;
        $this->assertSame(
            [['dsn' => 'foo:']],
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

        $transport = new TransportFactory('default');

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

        $transport = new TransportFactory('default');

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
