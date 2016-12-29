<?php
namespace Enqueue\Tests\Symfony;

use Enqueue\Symfony\DefaultTransportFactory;
use Enqueue\Symfony\TransportFactoryInterface;
use Enqueue\Test\ClassExtensionTrait;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class DefaultTransportFactoryTest extends \PHPUnit_Framework_TestCase
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

    public function testShouldAllowAddConfiguration()
    {
        $transport = new DefaultTransportFactory();
        $tb = new TreeBuilder();
        $rootNode = $tb->root('foo');

        $transport->addConfiguration($rootNode);
        $processor = new Processor();
        $config = $processor->process($tb->buildTree(), ['the_alias']);

        $this->assertEquals(['alias' => 'the_alias'], $config);
    }

    public function testShouldCreateContext()
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

    public function testShouldCreateDriver()
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
}
