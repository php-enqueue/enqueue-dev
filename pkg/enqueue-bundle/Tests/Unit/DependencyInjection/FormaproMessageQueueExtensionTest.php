<?php
namespace Enqueue\EnqueueBundle\Tests\Unit\DependencyInjection;

use Enqueue\Client\MessageProducer;
use Enqueue\Client\TraceableMessageProducer;
use Enqueue\Symfony\DefaultTransportFactory;
use Enqueue\Symfony\NullTransportFactory;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Transport\Null\NullContext;
use Enqueue\EnqueueBundle\DependencyInjection\Configuration;
use Enqueue\EnqueueBundle\DependencyInjection\EnqueueExtension;
use Enqueue\EnqueueBundle\Tests\Unit\Mocks\FooTransportFactory;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class EnqueueExtensionTest extends \PHPUnit_Framework_TestCase
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

    public function testShouldConfigureNullTransport()
    {
        $container = new ContainerBuilder();

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new NullTransportFactory());

        $extension->load([[
            'transport' => [
                'null' => true,
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('enqueue.transport.null.context'));
        $context = $container->getDefinition('enqueue.transport.null.context');
        self::assertEquals(NullContext::class, $context->getClass());
    }

    public function testShouldUseNullTransportAsDefault()
    {
        $container = new ContainerBuilder();

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new NullTransportFactory());
        $extension->addTransportFactory(new DefaultTransportFactory());

        $extension->load([[
            'transport' => [
                'default' => 'null',
                'null' => true,
            ],
        ]], $container);

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
        $container = new ContainerBuilder();

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new FooTransportFactory());

        $extension->load([[
            'transport' => [
                'foo' => ['foo_param' => 'aParam'],
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('foo.context'));
        $context = $container->getDefinition('foo.context');
        self::assertEquals(\stdClass::class, $context->getClass());
        self::assertEquals([['foo_param' => 'aParam']], $context->getArguments());
    }

    public function testShouldUseFooTransportAsDefault()
    {
        $container = new ContainerBuilder();

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new FooTransportFactory());
        $extension->addTransportFactory(new DefaultTransportFactory());

        $extension->load([[
            'transport' => [
                'default' => 'foo',
                'foo' => ['foo_param' => 'aParam'],
            ],
        ]], $container);

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
        $container = new ContainerBuilder();

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new DefaultTransportFactory());
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

        self::assertTrue($container->hasDefinition('enqueue.client.config'));
        self::assertTrue($container->hasDefinition('enqueue.client.message_producer'));
    }

    public function testShouldUseMessageProducerByDefault()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new DefaultTransportFactory());
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

        $messageProducer = $container->getDefinition('enqueue.client.message_producer');
        self::assertEquals(MessageProducer::class, $messageProducer->getClass());
    }

    public function testShouldUseMessageProducerIfTraceableProducerOptionSetToFalseExplicitly()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', false);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new DefaultTransportFactory());
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

        $messageProducer = $container->getDefinition('enqueue.client.message_producer');
        self::assertEquals(MessageProducer::class, $messageProducer->getClass());
    }

    public function testShouldUseTraceableMessageProducerIfTraceableProducerOptionSetToTrueExplicitly()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new DefaultTransportFactory());
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

        $messageProducer = $container->getDefinition('enqueue.client.traceable_message_producer');
        self::assertEquals(TraceableMessageProducer::class, $messageProducer->getClass());
        self::assertEquals(
            ['enqueue.client.message_producer', null, 0],
            $messageProducer->getDecoratedService()
        );

        self::assertInstanceOf(Reference::class, $messageProducer->getArgument(0));
        self::assertEquals(
            'enqueue.client.traceable_message_producer.inner',
            (string) $messageProducer->getArgument(0)
        );
    }

    public function testShouldLoadDelayRedeliveredMessageExtensionIfRedeliveredDelayTimeGreaterThenZero()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new DefaultTransportFactory());
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

        $extension = $container->getDefinition('enqueue.client.delay_redelivered_message_extension');

        self::assertEquals(12345, $extension->getArgument(1));
    }

    public function testShouldNotLoadDelayRedeliveredMessageExtensionIfRedeliveredDelayTimeIsZero()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new EnqueueExtension();
        $extension->addTransportFactory(new DefaultTransportFactory());
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

        $this->assertFalse($container->hasDefinition('enqueue.client.delay_redelivered_message_extension'));
    }

    public function testShouldLoadJobServicesIfEnabled()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'transport' => [],
            'job' => true,
        ]], $container);

        self::assertTrue($container->hasDefinition('enqueue.job.runner'));
    }

    public function testShouldNotLoadJobServicesIfDisabled()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'transport' => [],
            'job' => false,
        ]], $container);

        self::assertFalse($container->hasDefinition('enqueue.job.runner'));
    }

    public function testShouldAllowGetConfiguration()
    {
        $extension = new EnqueueExtension();

        $configuration = $extension->getConfiguration([], new ContainerBuilder());

        self::assertInstanceOf(Configuration::class, $configuration);
    }

    public function testShouldLoadDoctrinePingConnectionExtensionServiceIfEnabled()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'transport' => [],
            'extensions' => [
                'doctrine_ping_connection_extension' => true,
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('enqueue.consumption.doctrine_ping_connection_extension'));
    }

    public function testShouldNotLoadDoctrinePingConnectionExtensionServiceIfDisabled()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'transport' => [],
            'extensions' => [
                'doctrine_ping_connection_extension' => false,
            ],
        ]], $container);

        self::assertFalse($container->hasDefinition('enqueue.consumption.doctrine_ping_connection_extension'));
    }

    public function testShouldLoadDoctrineClearIdentityMapExtensionServiceIfEnabled()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'transport' => [],
            'extensions' => [
                'doctrine_clear_identity_map_extension' => true,
            ],
        ]], $container);

        self::assertTrue($container->hasDefinition('enqueue.consumption.doctrine_clear_identity_map_extension'));
    }

    public function testShouldNotLoadDoctrineClearIdentityMapExtensionServiceIfDisabled()
    {
        $container = new ContainerBuilder();
        $container->setParameter('kernel.debug', true);

        $extension = new EnqueueExtension();

        $extension->load([[
            'transport' => [],
            'extensions' => [
                'doctrine_clear_identity_map_extension' => false,
            ],
        ]], $container);

        self::assertFalse($container->hasDefinition('enqueue.consumption.doctrine_clear_identity_map_extension'));
    }
}
