<?php

namespace Enqueue\Tests\Symfony\DependencyInjection;

use Enqueue\Consumption\ChainExtension;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\Rpc\RpcClient;
use Enqueue\Rpc\RpcFactory;
use Enqueue\Symfony\DependencyInjection\TransportFactory;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\ConnectionFactory;
use Interop\Queue\Context;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Reference;

class TransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(TransportFactory::class);
    }

    public function testThrowIfEmptyNameGivenOnConstruction()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The name could not be empty.');

        new TransportFactory('');
    }

    public function testShouldAllowAddConfigurationAsStringDsn()
    {
        list($tb, $rootNode) = $this->getRootNode();

        $rootNode->append(TransportFactory::getConfiguration());

        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [['transport' => 'dsn://']]);

        $this->assertEquals([
            'transport' => [
                'dsn' => 'dsn://',
            ],
        ], $config);
    }

    /**
     * @see https://github.com/php-enqueue/enqueue-dev/issues/356
     *
     * @group bug
     */
    public function testShouldAllowAddConfigurationAsDsnWithoutSlashes()
    {
        list($tb, $rootNode) = $this->getRootNode();

        $rootNode->append(TransportFactory::getConfiguration());

        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [['transport' => 'dsn:']]);

        $this->assertEquals([
            'transport' => [
                'dsn' => 'dsn:',
            ],
        ], $config);
    }

    public function testShouldSetNullTransportIfNullGiven()
    {
        list($tb, $rootNode) = $this->getRootNode();

        $rootNode->append(TransportFactory::getConfiguration());

        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [['transport' => null]]);

        $this->assertEquals([
            'transport' => [
                'dsn' => 'null:',
            ],
        ], $config);
    }

    public function testShouldSetNullTransportIfEmptyStringGiven()
    {
        list($tb, $rootNode) = $this->getRootNode();

        $rootNode->append(TransportFactory::getConfiguration());

        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [['transport' => '']]);

        $this->assertEquals([
            'transport' => [
                'dsn' => 'null:',
            ],
        ], $config);
    }

    public function testShouldSetNullTransportIfEmptyArrayGiven()
    {
        list($tb, $rootNode) = $this->getRootNode();

        $rootNode->append(TransportFactory::getConfiguration());

        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [['transport' => []]]);

        $this->assertEquals([
            'transport' => [
                'dsn' => 'null:',
            ],
        ], $config);
    }

    public function testThrowIfEmptyDsnGiven()
    {
        list($tb, $rootNode) = $this->getRootNode();

        $rootNode->append(TransportFactory::getConfiguration());

        $processor = new Processor();
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The path "foo.transport.dsn" cannot contain an empty value, but got "".');
        $processor->process($tb->buildTree(), [['transport' => ['dsn' => '']]]);
    }

    public function testThrowIfFactoryClassAndFactoryServiceSetAtTheSameTime()
    {
        list($tb, $rootNode) = $this->getRootNode();

        $rootNode->append(TransportFactory::getConfiguration());

        $processor = new Processor();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Both options factory_class and factory_service are set. Please choose one.');
        $processor->process($tb->buildTree(), [[
            'transport' => [
                'dsn' => 'foo:',
                'factory_class' => 'aFactoryClass',
                'factory_service' => 'aFactoryService',
        ], ]]);
    }

    public function testThrowIfConnectionFactoryClassUsedWithFactoryClassAtTheSameTime()
    {
        list($tb, $rootNode) = $this->getRootNode();

        $rootNode->append(TransportFactory::getConfiguration());

        $processor = new Processor();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The option connection_factory_class must not be used with factory_class or factory_service at the same time. Please choose one.');
        $processor->process($tb->buildTree(), [[
            'transport' => [
                'dsn' => 'foo:',
                'connection_factory_class' => 'aFactoryClass',
                'factory_service' => 'aFactoryService',
        ], ]]);
    }

    public function testThrowIfConnectionFactoryClassUsedWithFactoryServiceAtTheSameTime()
    {
        list($tb, $rootNode) = $this->getRootNode();

        $rootNode->append(TransportFactory::getConfiguration());
        $processor = new Processor();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The option connection_factory_class must not be used with factory_class or factory_service at the same time. Please choose one.');
        $processor->process($tb->buildTree(), [[
            'transport' => [
                'dsn' => 'foo:',
                'connection_factory_class' => 'aFactoryClass',
                'factory_service' => 'aFactoryService',
        ], ]]);
    }

    public function testShouldAllowSetFactoryClass()
    {
        list($tb, $rootNode) = $this->getRootNode();

        $rootNode->append(TransportFactory::getConfiguration());
        $processor = new Processor();

        $config = $processor->process($tb->buildTree(), [[
            'transport' => [
                'dsn' => 'foo:',
                'factory_class' => 'theFactoryClass',
        ], ]]);

        $this->assertArrayHasKey('factory_class', $config['transport']);
        $this->assertSame('theFactoryClass', $config['transport']['factory_class']);
    }

    public function testShouldAllowSetFactoryService()
    {
        list($tb, $rootNode) = $this->getRootNode();

        $rootNode->append(TransportFactory::getConfiguration());
        $processor = new Processor();

        $config = $processor->process($tb->buildTree(), [[
            'transport' => [
                'dsn' => 'foo:',
                'factory_service' => 'theFactoryService',
        ], ]]);

        $this->assertArrayHasKey('factory_service', $config['transport']);
        $this->assertSame('theFactoryService', $config['transport']['factory_service']);
    }

    public function testShouldAllowSetConnectionFactoryClass()
    {
        list($tb, $rootNode) = $this->getRootNode();

        $rootNode->append(TransportFactory::getConfiguration());
        $processor = new Processor();

        $config = $processor->process($tb->buildTree(), [[
            'transport' => [
                'dsn' => 'foo:',
                'connection_factory_class' => 'theFactoryClass',
        ], ]]);

        $this->assertArrayHasKey('connection_factory_class', $config['transport']);
        $this->assertSame('theFactoryClass', $config['transport']['connection_factory_class']);
    }

    public function testThrowIfExtraOptionGiven()
    {
        list($tb, $rootNode) = $this->getRootNode();

        $rootNode->append(TransportFactory::getConfiguration());
        $processor = new Processor();

        $config = $processor->process($tb->buildTree(), [['transport' => ['dsn' => 'foo:', 'extraOption' => 'aVal']]]);
        $this->assertEquals([
            'transport' => [
                'dsn' => 'foo:',
                'extraOption' => 'aVal',
            ], ], $config
        );
    }

    public function testShouldBuildConnectionFactoryFromDSN()
    {
        $container = new ContainerBuilder();

        $transport = new TransportFactory('default');

        $transport->buildConnectionFactory($container, ['dsn' => 'foo://bar/baz']);

        $this->assertTrue($container->hasDefinition('enqueue.transport.default.connection_factory'));

        $this->assertNotEmpty($container->getDefinition('enqueue.transport.default.connection_factory')->getFactory());
        $this->assertEquals(
            [new Reference('enqueue.transport.default.connection_factory_factory'), 'create'],
            $container->getDefinition('enqueue.transport.default.connection_factory')->getFactory())
        ;
        $this->assertSame(
            [['dsn' => 'foo://bar/baz']],
            $container->getDefinition('enqueue.transport.default.connection_factory')->getArguments())
        ;
    }

    public function testShouldBuildConnectionFactoryUsingCustomFactoryClass()
    {
        $container = new ContainerBuilder();

        $transport = new TransportFactory('default');

        $transport->buildConnectionFactory($container, ['dsn' => 'foo:', 'factory_class' => 'theFactoryClass']);

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
    }

    public function testShouldBuildConnectionFactoryUsingCustomFactoryService()
    {
        $container = new ContainerBuilder();

        $transport = new TransportFactory('default');

        $transport->buildConnectionFactory($container, ['dsn' => 'foo:', 'factory_service' => 'theFactoryService']);

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
    }

    public function testShouldBuildConnectionFactoryUsingConnectionFactoryClassWithoutFactory()
    {
        $container = new ContainerBuilder();

        $transport = new TransportFactory('default');

        $transport->buildConnectionFactory($container, ['dsn' => 'foo:', 'connection_factory_class' => 'theFactoryClass']);

        $this->assertTrue($container->hasDefinition('enqueue.transport.default.connection_factory'));

        $this->assertEmpty($container->getDefinition('enqueue.transport.default.connection_factory')->getFactory());
        $this->assertSame('theFactoryClass', $container->getDefinition('enqueue.transport.default.connection_factory')->getClass());
        $this->assertSame(
            [['dsn' => 'foo:']],
            $container->getDefinition('enqueue.transport.default.connection_factory')->getArguments())
        ;
    }

    public function testShouldBuildContext()
    {
        $container = new ContainerBuilder();
        $container->register('enqueue.transport.default.connection_factory', ConnectionFactory::class);

        $transport = new TransportFactory('default');

        $transport->buildContext($container, []);

        $this->assertNotEmpty($container->getDefinition('enqueue.transport.default.context')->getFactory());
        $this->assertEquals(
            [new Reference('enqueue.transport.default.connection_factory'), 'createContext'],
            $container->getDefinition('enqueue.transport.default.context')->getFactory())
        ;
        $this->assertSame(
            [],
            $container->getDefinition('enqueue.transport.default.context')->getArguments())
        ;
    }

    public function testThrowIfBuildContextCalledButConnectionFactoryServiceDoesNotExist()
    {
        $container = new ContainerBuilder();

        $transport = new TransportFactory('default');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The service "enqueue.transport.default.connection_factory" does not exist.');
        $transport->buildContext($container, []);
    }

    public function testShouldBuildQueueConsumerWithDefaultOptions()
    {
        $container = new ContainerBuilder();
        $container->register('enqueue.transport.default.context', Context::class);

        $transport = new TransportFactory('default');

        $transport->buildQueueConsumer($container, []);

        $this->assertSame(10000, $container->getParameter('enqueue.transport.default.receive_timeout'));

        $this->assertTrue($container->hasDefinition('enqueue.transport.default.consumption_extensions'));
        $this->assertSame(ChainExtension::class, $container->getDefinition('enqueue.transport.default.consumption_extensions')->getClass());
        $this->assertSame([[]], $container->getDefinition('enqueue.transport.default.consumption_extensions')->getArguments());

        $this->assertTrue($container->hasDefinition('enqueue.transport.default.queue_consumer'));
        $this->assertSame(QueueConsumer::class, $container->getDefinition('enqueue.transport.default.queue_consumer')->getClass());
        $this->assertEquals([
            new Reference('enqueue.transport.default.context'),
            new Reference('enqueue.transport.default.consumption_extensions'),
            [],
            new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            '%enqueue.transport.default.receive_timeout%',
        ], $container->getDefinition('enqueue.transport.default.queue_consumer')->getArguments());
    }

    public function testShouldBuildQueueConsumerWithCustomOptions()
    {
        $container = new ContainerBuilder();
        $container->register('enqueue.transport.default.context', Context::class);

        $transport = new TransportFactory('default');

        $transport->buildQueueConsumer($container, [
            'receive_timeout' => 567,
        ]);

        $this->assertSame(567, $container->getParameter('enqueue.transport.default.receive_timeout'));
    }

    public function testThrowIfBuildQueueConsumerCalledButContextServiceDoesNotExist()
    {
        $container = new ContainerBuilder();

        $transport = new TransportFactory('default');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The service "enqueue.transport.default.context" does not exist.');
        $transport->buildQueueConsumer($container, []);
    }

    public function testShouldBuildRpcClientWithDefaultOptions()
    {
        $container = new ContainerBuilder();
        $container->register('enqueue.transport.default.context', Context::class);

        $transport = new TransportFactory('default');

        $transport->buildRpcClient($container, []);

        $this->assertTrue($container->hasDefinition('enqueue.transport.default.rpc_factory'));
        $this->assertSame(RpcFactory::class, $container->getDefinition('enqueue.transport.default.rpc_factory')->getClass());

        $this->assertTrue($container->hasDefinition('enqueue.transport.default.rpc_client'));
        $this->assertSame(RpcClient::class, $container->getDefinition('enqueue.transport.default.rpc_client')->getClass());
        $this->assertEquals([
            new Reference('enqueue.transport.default.context'),
            new Reference('enqueue.transport.default.rpc_factory'),
        ], $container->getDefinition('enqueue.transport.default.rpc_client')->getArguments());
    }

    public function testThrowIfBuildRpcClientCalledButContextServiceDoesNotExist()
    {
        $container = new ContainerBuilder();

        $transport = new TransportFactory('default');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The service "enqueue.transport.default.context" does not exist.');
        $transport->buildRpcClient($container, []);
    }

    /**
     * @return [TreeBuilder, NodeDefinition]
     */
    private function getRootNode(): array
    {
        if (method_exists(TreeBuilder::class, 'getRootNode')) {
            $tb = new TreeBuilder('foo');

            return [$tb, $tb->getRootNode()];
        }

        $tb = new TreeBuilder();

        return [$tb, $tb->root('foo')];
    }
}
