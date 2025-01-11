<?php

namespace Enqueue\Bundle\Tests\Unit\DependencyInjection;

use Enqueue\Bundle\DependencyInjection\Configuration;
use Enqueue\Bundle\DependencyInjection\EnqueueExtension;
use Enqueue\Client\CommandSubscriberInterface;
use Enqueue\Client\Producer;
use Enqueue\Client\ProducerInterface;
use Enqueue\Client\TopicSubscriberInterface;
use Enqueue\Client\TraceableProducer;
use Enqueue\JobQueue\JobRunner;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class EnqueueExtensionTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConfigurationInterface()
    {
        $this->assertClassExtends(Extension::class, EnqueueExtension::class);
    }

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(EnqueueExtension::class);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCouldBeConstructedWithoutAnyArguments()
    {
        new EnqueueExtension();
    }

    public function testShouldRegisterConnectionFactory()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => null,
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('enqueue.transport.default.connection_factory'));
        self::assertNotEmpty($container->getDefinition('enqueue.transport.default.connection_factory')->getFactory());
    }

    public function testShouldRegisterContext()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => null,
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('enqueue.transport.default.context'));
        self::assertNotEmpty($container->getDefinition('enqueue.transport.default.context')->getFactory());
    }

    public function testShouldRegisterClientDriver()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => null,
                'client' => true,
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('enqueue.client.default.driver'));
        self::assertNotEmpty($container->getDefinition('enqueue.client.default.driver')->getFactory());
    }

    public function testShouldLoadClientServicesWhenEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'client' => null,
                'transport' => 'null:',
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('enqueue.client.default.driver'));
        self::assertTrue($container->hasDefinition('enqueue.client.default.config'));
        self::assertTrue($container->hasAlias(ProducerInterface::class));
    }

    public function testShouldUseProducerByDefault()
    {
        $container = $this->getContainerBuilder(false);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'client' => null,
                'transport' => 'null',
            ],
        ]], $container);

        $producer = $container->getDefinition('enqueue.client.default.producer');
        self::assertEquals(Producer::class, $producer->getClass());
    }

    public function testShouldUseMessageProducerIfTraceableProducerOptionSetToFalseExplicitly()
    {
        $container = $this->getContainerBuilder(false);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'client' => [
                    'traceable_producer' => false,
                ],
                'transport' => 'null:',
            ],
        ]], $container);

        $producer = $container->getDefinition('enqueue.client.default.producer');
        self::assertEquals(Producer::class, $producer->getClass());
    }

    public function testShouldUseTraceableMessageProducerIfDebugEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => 'null:',
                'client' => null,
            ],
        ]], $container);

        $producer = $container->getDefinition('enqueue.client.default.traceable_producer');
        self::assertEquals(TraceableProducer::class, $producer->getClass());
        self::assertEquals(
            ['enqueue.client.default.producer', null, 0],
            $producer->getDecoratedService()
        );

        self::assertInstanceOf(Reference::class, $producer->getArgument(0));

        $innerServiceName = 'enqueue.client.default.traceable_producer.inner';

        self::assertEquals(
            $innerServiceName,
            (string) $producer->getArgument(0)
        );
    }

    public function testShouldNotUseTraceableMessageProducerIfDebugDisabledAndNotSetExplicitly()
    {
        $container = $this->getContainerBuilder(false);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => 'null:',
            ],
        ]], $container);

        $this->assertFalse($container->hasDefinition('enqueue.client.default.traceable_producer'));
    }

    public function testShouldUseTraceableMessageProducerIfDebugDisabledButTraceableProducerOptionSetToTrueExplicitly()
    {
        $container = $this->getContainerBuilder(false);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'client' => [
                    'traceable_producer' => true,
                ],
                'transport' => 'null:',
            ],
        ]], $container);

        $producer = $container->getDefinition('enqueue.client.default.traceable_producer');
        self::assertEquals(TraceableProducer::class, $producer->getClass());
        self::assertEquals(
            ['enqueue.client.default.producer', null, 0],
            $producer->getDecoratedService()
        );

        self::assertInstanceOf(Reference::class, $producer->getArgument(0));

        $innerServiceName = 'enqueue.client.default.traceable_producer.inner';

        self::assertEquals(
            $innerServiceName,
            (string) $producer->getArgument(0)
        );
    }

    public function testShouldLoadDelayRedeliveredMessageExtensionIfRedeliveredDelayTimeGreaterThenZero()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => 'null:',
                'client' => [
                    'redelivered_delay_time' => 12345,
                ],
            ],
        ]], $container);

        $extension = $container->getDefinition('enqueue.client.default.delay_redelivered_message_extension');

        self::assertEquals(12345, $extension->getArgument(1));
    }

    public function testShouldNotLoadDelayRedeliveredMessageExtensionIfRedeliveredDelayTimeIsZero()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => 'null:',
                'client' => [
                    'redelivered_delay_time' => 0,
                ],
            ],
        ]], $container);

        $this->assertFalse($container->hasDefinition('enqueue.client.default.delay_redelivered_message_extension'));
    }

    public function testShouldLoadJobServicesIfEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'client' => null,
                'job' => true,
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition(JobRunner::class));
    }

    public function testShouldThrowExceptionIfClientIsNotEnabledOnJobLoad()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Client is required for job-queue.');

        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'job' => true,
            ],
        ]], $container);
    }

    public function testShouldNotLoadJobServicesIfDisabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'job' => false,
            ],
        ]], $container);

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
            'default' => [
                'transport' => [],
                'extensions' => [
                    'doctrine_ping_connection_extension' => true,
                ],
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('enqueue.consumption.doctrine_ping_connection_extension'));
    }

    public function testShouldNotLoadDoctrinePingConnectionExtensionServiceIfDisabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'doctrine_ping_connection_extension' => false,
                ],
            ],
        ]], $container);

        self::assertFalse($container->hasDefinition('enqueue.consumption.doctrine_ping_connection_extension'));
    }

    public function testShouldLoadDoctrineClearIdentityMapExtensionServiceIfEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'doctrine_clear_identity_map_extension' => true,
                ],
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('enqueue.consumption.doctrine_clear_identity_map_extension'));
    }

    public function testShouldNotLoadDoctrineClearIdentityMapExtensionServiceIfDisabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'doctrine_clear_identity_map_extension' => false,
                ],
            ],
        ]], $container);

        self::assertFalse($container->hasDefinition('enqueue.consumption.doctrine_clear_identity_map_extension'));
    }

    public function testShouldLoadDoctrineOdmClearIdentityMapExtensionServiceIfEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'doctrine_odm_clear_identity_map_extension' => true,
                ],
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('enqueue.consumption.doctrine_odm_clear_identity_map_extension'));
    }

    public function testShouldNotLoadDoctrineOdmClearIdentityMapExtensionServiceIfDisabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'doctrine_odm_clear_identity_map_extension' => false,
                ],
            ],
        ]], $container);

        self::assertFalse($container->hasDefinition('enqueue.consumption.doctrine_odm_clear_identity_map_extension'));
    }

    public function testShouldLoadDoctrineClosedEntityManagerExtensionServiceIfEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'doctrine_closed_entity_manager_extension' => true,
                ],
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('enqueue.consumption.doctrine_closed_entity_manager_extension'));
    }

    public function testShouldNotLoadDoctrineClosedEntityManagerExtensionServiceIfDisabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'doctrine_closed_entity_manager_extension' => false,
                ],
            ],
        ]], $container);

        self::assertFalse($container->hasDefinition('enqueue.consumption.doctrine_closed_entity_manager_extension'));
    }

    public function testShouldLoadResetServicesExtensionServiceIfEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'reset_services_extension' => true,
                ],
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('enqueue.consumption.reset_services_extension'));
    }

    public function testShouldNotLoadResetServicesExtensionServiceIfDisabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'reset_services_extension' => false,
                ],
            ],
        ]], $container);

        self::assertFalse($container->hasDefinition('enqueue.consumption.reset_services_extension'));
    }

    public function testShouldLoadSignalExtensionServiceIfEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'signal_extension' => true,
                ],
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('enqueue.consumption.signal_extension'));
    }

    public function testShouldNotLoadSignalExtensionServiceIfDisabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'signal_extension' => false,
                ],
            ],
        ]], $container);

        self::assertFalse($container->hasDefinition('enqueue.consumption.signal_extension'));
    }

    public function testShouldLoadReplyExtensionServiceIfEnabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'reply_extension' => true,
                ],
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('enqueue.consumption.reply_extension'));
    }

    public function testShouldNotLoadReplyExtensionServiceIfDisabled()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'transport' => [],
                'extensions' => [
                    'reply_extension' => false,
                ],
            ],
        ]], $container);

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
            'default' => [
                'client' => [],
                'transport' => [
                ],
                'consumption' => [
                    'receive_timeout' => 456,
                ],
            ],
        ]], $container);

        $def = $container->getDefinition('enqueue.transport.default.queue_consumer');
        $this->assertSame('%enqueue.transport.default.receive_timeout%', $def->getArgument(4));

        $this->assertSame(456, $container->getParameter('enqueue.transport.default.receive_timeout'));

        $def = $container->getDefinition('enqueue.client.default.queue_consumer');
        $this->assertSame(456, $def->getArgument(4));
    }

    public function testShouldSetPropertyWithAllConfiguredTransports()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();
        $extension->load([[
            'default' => [
                'transport' => 'default:',
                'client' => [],
            ],
            'foo' => [
                'transport' => 'foo:',
                'client' => [],
            ],
            'bar' => [
                'transport' => 'bar:',
                'client' => [],
            ],
        ]], $container);

        $this->assertTrue($container->hasParameter('enqueue.transports'));
        $this->assertEquals(['default', 'foo', 'bar'], $container->getParameter('enqueue.transports'));
    }

    public function testShouldSetPropertyWithAllConfiguredClients()
    {
        $container = $this->getContainerBuilder(true);

        $extension = new EnqueueExtension();
        $extension->load([[
            'default' => [
                'transport' => 'default:',
                'client' => [],
            ],
            'foo' => [
                'transport' => 'foo:',
            ],
            'bar' => [
                'transport' => 'bar:',
                'client' => [],
            ],
        ]], $container);

        $this->assertTrue($container->hasParameter('enqueue.clients'));
        $this->assertEquals(['default', 'bar'], $container->getParameter('enqueue.clients'));
    }

    public function testShouldLoadProcessAutoconfigureChildDefinition()
    {
        $container = $this->getContainerBuilder(true);
        $extension = new EnqueueExtension();

        $extension->load([[
            'default' => [
                'client' => [],
                'transport' => [],
            ],
        ]], $container);

        $autoconfigured = $container->getAutoconfiguredInstanceof();

        self::assertArrayHasKey(CommandSubscriberInterface::class, $autoconfigured);
        self::assertTrue($autoconfigured[CommandSubscriberInterface::class]->hasTag('enqueue.command_subscriber'));
        self::assertTrue($autoconfigured[CommandSubscriberInterface::class]->isPublic());

        self::assertArrayHasKey(TopicSubscriberInterface::class, $autoconfigured);
        self::assertTrue($autoconfigured[TopicSubscriberInterface::class]->hasTag('enqueue.topic_subscriber'));
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
