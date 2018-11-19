<?php

namespace Enqueue\Tests\Symfony\Client\DependencyInjection;

use Enqueue\Symfony\Client\DependencyInjection\ClientFactory;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class ClientFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldBeFinal()
    {
        $this->assertClassFinal(ClientFactory::class);
    }

    public function testThrowIfEmptyNameGivenOnConstruction()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The name could not be empty.');

        new ClientFactory('');
    }

    public function testShouldCreateDriverFromDsn()
    {
        $container = new ContainerBuilder();

        $transport = new ClientFactory('default');

        $serviceId = $transport->createDriver($container, ['dsn' => 'foo://bar/baz', 'foo' => 'fooVal']);

        $this->assertEquals('enqueue.client.default.driver', $serviceId);

        $this->assertTrue($container->hasDefinition('enqueue.client.default.driver'));

        $this->assertNotEmpty($container->getDefinition('enqueue.client.default.driver')->getFactory());
        $this->assertEquals(
            [new Reference('enqueue.client.default.driver_factory'), 'create'],
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
    }
}
