<?php

namespace Enqueue\Tests\Symfony;

use Enqueue\Psr\PsrConnectionFactory;
use Enqueue\Symfony\DsnTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class DsnTransportFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementTransportFactoryInterface()
    {
        $this->assertClassImplements(TransportFactoryInterface::class, DsnTransportFactory::class);
    }

    public function testCouldBeConstructedWithDefaultName()
    {
        $transport = new DsnTransportFactory([]);

        $this->assertEquals('dsn', $transport->getName());
    }

    public function testCouldBeConstructedWithCustomName()
    {
        $transport = new DsnTransportFactory([], 'theCustomName');

        $this->assertEquals('theCustomName', $transport->getName());
    }

    public function testShouldAllowAddConfigurationAsString()
    {
        $transport = new DsnTransportFactory([]);
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['amqp://example.com']);

        $this->assertEquals(['dsn' => 'amqp://example.com'], $config);
    }

    public function testShouldAllowAddConfigurationAsOption()
    {
        $transport = new DsnTransportFactory([]);
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), [['dsn' => 'amqp://example.com']]);

        $this->assertEquals(['dsn' => 'amqp://example.com'], $config);
    }

    public function testThrowIfSchemeNotParsedOnCreateConnectionFactory()
    {
        $transport = new DsnTransportFactory([]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The scheme could not be parsed from DSN "invalidDsn"');

        $transport->createConnectionFactory(new ContainerBuilder(), ['dsn' => 'invalidDsn']);
    }

    public function testThrowIfSchemeNotSupportedOnCreateConnectionFactory()
    {
        $transport = new DsnTransportFactory([]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The scheme "http" is not supported.');

        $transport->createConnectionFactory(new ContainerBuilder(), ['dsn' => 'http://foo.bar']);
    }

    public function testThrowIfThereIsFactoryRegistered()
    {
        $transport = new DsnTransportFactory([
            $this->createTransportFactoryStub('foo'),
            $this->createTransportFactoryStub('bar'),
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('There is no factory that supports requested schema "amqp", available are "foo", "bar"');

        $transport->createConnectionFactory(new ContainerBuilder(), ['dsn' => 'amqp://foo']);
    }

    public function testShouldProxyCallToInternalFactoryCreateConnectionFactoryMethod()
    {
        $container = new ContainerBuilder();

        $internalFactory = $this->createTransportFactoryStub('amqp');
        $internalFactory
            ->expects($this->once())
            ->method('createConnectionFactory')
            ->with($this->identicalTo($container), ['dsn' => 'amqp://example.com'])
            ->willReturn('theServiceId')
        ;

        $transport = new DsnTransportFactory([$internalFactory]);

        $serviceId = $transport->createConnectionFactory($container, ['dsn' => 'amqp://example.com']);

        $this->assertEquals('theServiceId', $serviceId);
    }

    public function testShouldProxyCallToInternalCreateContextMethod()
    {
        $container = new ContainerBuilder();

        $internalFactory = $this->createTransportFactoryStub('amqp');
        $internalFactory
            ->expects($this->once())
            ->method('createContext')
            ->with($this->identicalTo($container), ['dsn' => 'amqp://example.com'])
            ->willReturn('theServiceId')
        ;

        $transport = new DsnTransportFactory([$internalFactory]);

        $serviceId = $transport->createContext($container, ['dsn' => 'amqp://example.com']);

        $this->assertEquals('theServiceId', $serviceId);
    }

    public function testShouldProxyCallToInternalCreateDriverMethod()
    {
        $container = new ContainerBuilder();

        $internalFactory = $this->createTransportFactoryStub('amqp');
        $internalFactory
            ->expects($this->once())
            ->method('createDriver')
            ->with($this->identicalTo($container), ['dsn' => 'amqp://example.com'])
            ->willReturn('theServiceId')
        ;

        $transport = new DsnTransportFactory([$internalFactory]);

        $serviceId = $transport->createDriver($container, ['dsn' => 'amqp://example.com']);

        $this->assertEquals('theServiceId', $serviceId);
    }

    /**
     * @param mixed $name
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|TransportFactoryInterface
     */
    private function createTransportFactoryStub($name)
    {
        $factoryMock = $this->createMock(TransportFactoryInterface::class);
        $factoryMock
            ->expects($this->any())
            ->method('getName')
            ->willReturn($name)
        ;

        return $factoryMock;
    }
}
