<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection;

use Enqueue\Bundle\DependencyInjection\Compiler\BuildTransportFactoriesPass;
use Enqueue\Bundle\DependencyInjection\Configuration;
use Enqueue\Bundle\DependencyInjection\EnqueueExtension;
use Enqueue\Bundle\Tests\Unit\Mocks\FooTransportFactory;
use Enqueue\Bundle\Tests\Unit\Mocks\TransportFactoryWithoutDriverFactory;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Client\Producer;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Client\TraceableProducer;
use Enqueue\Consumption\QueueConsumer;
use Enqueue\JobQueue\JobRunner;
use Enqueue\Null\NullContext;
use Enqueue\Null\Symfony\NullTransportFactory;
use Enqueue\Symfony\DefaultTransportFactory;
use Enqueue\Symfony\MissingTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\HttpKernel\Kernel;

class EnqueueExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConfigurationInterface()
    {
        self::assertClassExtends(Extension::class, EnqueueExtension::class);
    }

    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new EnqueueExtension();
    }

    public function testShouldRegisterDefaultAndNullTransportFactoriesInConstructor()
    {
        $extension = new EnqueueExtension();

        /** @var TransportFactoryInterface[] $factories */
        $factories = $this->readAttribute($extension, 'factories');

        $this->assertInternalType('array', $factories);
        $this->assertCount(2, $factories);

        $this->assertArrayHasKey('default', $factories);
        $this->assertInstanceOf(DefaultTransportFactory::class, $factories['default']);
        $this->assertEquals('default', $factories['default']->getName());

        $this->assertArrayHasKey('null', $factories);
        $this->assertInstanceOf(NullTransportFactory::class, $factories['null']);
        $this->assertEquals('null', $factories['null']->getName());
    }

    public function testThrowIfTransportFactoryNameEmpty()
    {
        $extension = new EnqueueExtension();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Transport factory name cannot be empty');

        $extension->addTransportFactory(new FooTransportFactory(null));
    }

    public function testThrowIfTransportFactoryWithSameNameAlreadyAdded()
    {
        $extension = new EnqueueExtension();

        $extension->addTransportFactory(new FooTransportFactory('foo'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Transport factory with such name already added. Name foo');

        $extension->addTransportFactory(new FooTransportFactory('foo'));
    }

    public function testShouldEnabledNullTransportAndSetItAsDefault()
    {
        $container = $this->getContainerBuilder(true);
        $pass = new BuildTransportFactoriesPass();

        $extension = new EnqueueExtension();
        
        $extension->load([[
            'transport' => [
                'default' => 'null',
                'null' => true,
            ],
        ]], $container);
        $container->registerExtension($extension);

        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertTrue($container->hasAlias('enqueue.transport.default.context'));
        self::assertEquals('enqueue.transport.null.context', (string) $container->getAlias('enqueue.transport.default.context'));

        self::assertTrue($container->hasDefinition('enqueue.transport.null.context'));
        $context = $container->getDefinition('enqueue.transport.null.context');
        self::assertEquals(NullContext::class, $context->getClass());
    }

    public function testShouldUseNullTransportAsDefaultWhenExplicitlyConfigured()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();
        
        $extension->load([[
            'transport' => [
                'default' => 'null',
                'null' => true,
            ],
        ]], $container);
        $container->registerExtension($extension);

        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertEquals(
            'enqueue.transport.default.context',
            (string) $container->getAlias('enqueue.transport.context')
        );
        self::assertEquals(
            'enqueue.transport.null.context',
            (string) $container->getAlias('enqueue.transport.default.context')
        );
    }

    public function testShouldConfigureFooTransport()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new FooTransportFactory());
        
        $extension->load([[
            'transport' => [
                'default' => 'foo',
                'foo' => ['foo_param' => 'aParam'],
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition('foo.connection_factory'));
        self::assertTrue($container->hasDefinition('foo.context'));
        self::assertFalse($container->hasDefinition('foo.driver'));

        $context = $container->getDefinition('foo.context');
        self::assertEquals(\stdClass::class, $context->getClass());
        self::assertEquals([['foo_param' => 'aParam']], $context->getArguments());
    }

    public function testShouldUseFooTransportAsDefault()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new FooTransportFactory());
        
        $extension->load([[
            'transport' => [
                'default' => 'foo',
                'foo' => ['foo_param' => 'aParam'],
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertEquals(
            'enqueue.transport.default.context',
            (string) $container->getAlias('enqueue.transport.context')
        );
        self::assertEquals(
            'enqueue.transport.foo.context',
            (string) $container->getAlias('enqueue.transport.default.context')
        );
    }

    public function testShouldLoadClientServicesWhenEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new FooTransportFactory());
        
        $extension->load([[
            'client' => null,
            'transport' => [
                'default' => 'foo',
                'foo' => [
                    'foo_param' => true,
                ],
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition('foo.driver'));
        self::assertTrue($container->hasDefinition('enqueue.client.config'));
        self::assertTrue($container->hasDefinition(Producer::class));
        self::assertTrue($container->hasAlias(ProducerInterface::class));
    }

    public function testShouldNotCreateDriverIfFactoryDoesNotImplementDriverFactoryInterface()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new TransportFactoryWithoutDriverFactory());
        
        $extension->load([[
            'client' => null,
            'transport' => [
                'default' => 'without_driver',
                'without_driver' => [],
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition('without_driver.context'));
        self::assertTrue($container->hasDefinition('without_driver.connection_factory'));
        self::assertFalse($container->hasDefinition('without_driver.driver'));
    }

    public function testShouldUseProducerByDefault()
    {
        $container = $this->getContainerBuilder(false);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new FooTransportFactory());
        
        $extension->load([[
            'client' => null,
            'transport' => [
                'default' => 'foo',
                'foo' => [
                    'foo_param' => true,
                ],
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        $producer = $container->getDefinition(Producer::class);
        self::assertEquals(Producer::class, $producer->getClass());
    }

    public function testShouldUseMessageProducerIfTraceableProducerOptionSetToFalseExplicitly()
    {
        $container = $this->getContainerBuilder(false);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new FooTransportFactory());
        
        $extension->load([[
            'client' => [
                'traceable_producer' => false,
            ],
            'transport' => [
                'default' => 'foo',
                'foo' => [
                    'foo_param' => true,
                ],
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        $producer = $container->getDefinition(Producer::class);
        self::assertEquals(Producer::class, $producer->getClass());
    }

    public function testShouldUseTraceableMessageProducerIfDebugEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new FooTransportFactory());
        
        $extension->load([[
            'transport' => [
                'default' => 'foo',
                'foo' => [
                    'foo_param' => true,
                ],
            ],
            'client' => null,
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        $producer = $container->getDefinition(TraceableProducer::class);
        self::assertEquals(TraceableProducer::class, $producer->getClass());
        self::assertEquals(
            [Producer::class, null, 0],
            $producer->getDecoratedService()
        );

        self::assertInstanceOf(Reference::class, $producer->getArgument(0));

        $innerServiceName = sprintf('%s.inner', TraceableProducer::class);
        if (30300 > Kernel::VERSION_ID) {
            // Symfony 3.2 and below make service identifiers lowercase, so we do the same.
            $innerServiceName = strtolower($innerServiceName);
        }

        self::assertEquals(
            $innerServiceName,
            (string) $producer->getArgument(0)
        );
    }

    public function testShouldNotUseTraceableMessageProducerIfDebugDisabledAndNotSetExplicitly()
    {
        $container = $this->getContainerBuilder(false);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new FooTransportFactory());
        
        $extension->load([[
            'transport' => [
                'default' => 'foo',
                'foo' => [
                    'foo_param' => true,
                ],
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition(TraceableProducer::class));
    }

    public function testShouldUseTraceableMessageProducerIfDebugDisabledButTraceableProducerOptionSetToTrueExplicitly()
    {
        $container = $this->getContainerBuilder(false);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new FooTransportFactory());
        
        $extension->load([[
            'client' => [
                'traceable_producer' => true,
            ],
            'transport' => [
                'default' => 'foo',
                'foo' => [
                    'foo_param' => true,
                ],
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        $producer = $container->getDefinition(TraceableProducer::class);
        self::assertEquals(TraceableProducer::class, $producer->getClass());
        self::assertEquals(
            [Producer::class, null, 0],
            $producer->getDecoratedService()
        );

        self::assertInstanceOf(Reference::class, $producer->getArgument(0));

        $innerServiceName = sprintf('%s.inner', TraceableProducer::class);
        if (30300 > Kernel::VERSION_ID) {
            // Symfony 3.2 and below make service identifiers lowercase, so we do the same.
            $innerServiceName = strtolower($innerServiceName);
        }

        self::assertEquals(
            $innerServiceName,
            (string) $producer->getArgument(0)
        );
    }

    public function testShouldLoadDelayRedeliveredMessageExtensionIfRedeliveredDelayTimeGreaterThenZero()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new FooTransportFactory());
        
        $extension->load([[
            'transport' => [
                'default' => 'foo',
                'foo' => [
                    'foo_param' => true,
                ],
            ],
            'client' => [
                'redelivered_delay_time' => 12345,
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        $extension = $container->getDefinition('enqueue.client.delay_redelivered_message_extension');

        self::assertEquals(12345, $extension->getArgument(1));
    }

    public function testShouldNotLoadDelayRedeliveredMessageExtensionIfRedeliveredDelayTimeIsZero()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new FooTransportFactory());
        
        $extension->load([[
            'transport' => [
                'default' => 'foo',
                'foo' => [
                    'foo_param' => true,
                ],
            ],
            'client' => [
                'redelivered_delay_time' => 0,
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        $this->assertFalse($container->hasDefinition('enqueue.client.delay_redelivered_message_extension'));
    }

    public function testShouldLoadJobServicesIfEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();
        
        $extension->load([[
            'transport' => [],
            'job' => true,
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition(JobRunner::class));
    }

    public function testShouldNotLoadJobServicesIfDisabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();
        
        $extension->load([[
            'transport' => [],
            'job' => false,
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertFalse($container->hasDefinition(JobRunner::class));
    }

    public function testShouldAllowGetConfiguration()
    {
        $extension = new EnqueueExtension();

        $configuration = $extension->getConfiguration([], $this->getContainerBuilder(true));

        self::assertInstanceOf(Configuration::class, $configuration);
    }

    public function testShouldLoadDoctrinePingConnectionExtensionServiceIfEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'transport' => [],
            'extensions' => [
                'doctrine_ping_connection_extension' => true,
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition('enqueue.consumption.doctrine_ping_connection_extension'));
    }

    public function testShouldNotLoadDoctrinePingConnectionExtensionServiceIfDisabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'transport' => [],
            'extensions' => [
                'doctrine_ping_connection_extension' => false,
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertFalse($container->hasDefinition('enqueue.consumption.doctrine_ping_connection_extension'));
    }

    public function testShouldLoadDoctrineClearIdentityMapExtensionServiceIfEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'transport' => [],
            'extensions' => [
                'doctrine_clear_identity_map_extension' => true,
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition('enqueue.consumption.doctrine_clear_identity_map_extension'));
    }

    public function testShouldNotLoadDoctrineClearIdentityMapExtensionServiceIfDisabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'transport' => [],
            'extensions' => [
                'doctrine_clear_identity_map_extension' => false,
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertFalse($container->hasDefinition('enqueue.consumption.doctrine_clear_identity_map_extension'));
    }

    public function testShouldLoadSignalExtensionServiceIfEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'transport' => [],
            'extensions' => [
                'signal_extension' => true,
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition('enqueue.consumption.signal_extension'));
    }

    public function testShouldNotLoadSignalExtensionServiceIfDisabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'transport' => [],
            'extensions' => [
                'signal_extension' => false,
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertFalse($container->hasDefinition('enqueue.consumption.signal_extension'));
    }

    public function testShouldLoadReplyExtensionServiceIfEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'transport' => [],
            'extensions' => [
                'reply_extension' => true,
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertTrue($container->hasDefinition('enqueue.consumption.reply_extension'));
    }

    public function testShouldNotLoadReplyExtensionServiceIfDisabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'transport' => [],
            'extensions' => [
                'reply_extension' => false,
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        self::assertFalse($container->hasDefinition('enqueue.consumption.reply_extension'));
    }

    public function testShouldAddJobQueueEntityMapping()
    {
        $container = $this->getContainerBuilder(true);
        $container->setParameter('kernel.bundles', ['DoctrineBundle' => true]);
        $container->prependExtensionConfig('doctrine', ['dbal' => true]);

        $extension = new EnqueueExtension();

        $extension->prepend($container);

        $config = $container->getExtensionConfig('doctrine');

        $this->assertSame(['dbal' => true], $config[1]);
        $this->assertNotEmpty($config[0]['orm']['mappings']['enqueue_job_queue']);
    }

    public function testShouldNotAddJobQueueEntityMappingIfDoctrineBundleIsNotRegistered()
    {
        $container = $this->getContainerBuilder(true);
        $container->setParameter('kernel.bundles', []);

        $extension = new EnqueueExtension();

        $extension->prepend($container);

        $this->assertSame([], $container->getExtensionConfig('doctrine'));
    }

    public function testShouldConfigureQueueConsumer()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();
        $extension->load([[
            'client' => [],
            'transport' => [
            ],
            'consumption' => [
                'idle_timeout' => 123,
                'receive_timeout' => 456,
            ],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        $def = $container->getDefinition(QueueConsumer::class);
        $this->assertSame(123, $def->getArgument(2));
        $this->assertSame(456, $def->getArgument(3));

        $def = $container->getDefinition('enqueue.client.queue_consumer');
        $this->assertSame(123, $def->getArgument(2));
        $this->assertSame(456, $def->getArgument(3));
    }

    public function testShouldThrowIfPackageShouldBeInstalledToUseTransport()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new MissingTransportFactory('need_package', ['a_package', 'another_package']));

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('In order to use the transport "need_package" install');
        $extension->load([[
            'transport' => [
                'need_package' => true,
            ],
        ]], $container);
    }

    public function testShouldLoadProcessAutoconfigureChildDefinition()
    {
        if (30300 >= Kernel::VERSION_ID) {
            $this->markTestSkipped('The autoconfigure feature is available since Symfony 3.3 version');
        }

        $container = $this->getContainerBuilder(true);
        $extension = new EnqueueExtension();

        $extension->load([[
            'client' => [],
            'transport' => [],
        ]], $container);
        $container->registerExtension($extension);
        
        $pass = new BuildTransportFactoriesPass();
        $pass->process($container);

        $autoconfigured = $container->getAutoconfiguredInstanceof();

        self::assertArrayHasKey(CommandSubscriberInterface::class, $autoconfigured);
        self::assertTrue($autoconfigured[CommandSubscriberInterface::class]->hasTag('enqueue.client.processor'));
        self::assertTrue($autoconfigured[CommandSubscriberInterface::class]->isPublic());

        self::assertArrayHasKey(TopicSubscriberInterface::class, $autoconfigured);
        self::assertTrue($autoconfigured[TopicSubscriberInterface::class]->hasTag('enqueue.client.processor'));
        self::assertTrue($autoconfigured[TopicSubscriberInterface::class]->isPublic());
    }

    /**
     * @param bool $debug
     *
     * @return ContainerBuilder
     */
    private function getContainerBuilder($debug)
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', $debug);

        return $container;
    }
}
