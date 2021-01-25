<?php

namespace Enqueue\Dbal\Tests;

use Doctrine\DBAL\Connection;
use Doctrine\Persistence\ManagerRegistry;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use Interop\Queue\ConnectionFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ManagerRegistryConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(ConnectionFactory::class, ManagerRegistryConnectionFactory::class);
    }

    public function testCouldBeConstructedWithEmptyConfiguration()
    {
        $factory = new ManagerRegistryConnectionFactory($this->createManagerRegistryMock());

        $this->assertAttributeEquals([
            'lazy' => true,
            'connection_name' => null,
        ], 'config', $factory);
    }

    public function testCouldBeConstructedWithCustomConfiguration()
    {
        $factory = new ManagerRegistryConnectionFactory($this->createManagerRegistryMock(), [
            'connection_name' => 'theConnectionName',
            'lazy' => false,
        ]);

        $this->assertAttributeEquals([
            'lazy' => false,
            'connection_name' => 'theConnectionName',
        ], 'config', $factory);
    }

    public function testShouldCreateContext()
    {
        $registry = $this->createManagerRegistryMock();
        $registry
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($this->createConnectionMock())
        ;

        $factory = new ManagerRegistryConnectionFactory($registry, ['lazy' => false]);

        $context = $factory->createContext();

        $this->assertInstanceOf(DbalContext::class, $context);

        $this->assertAttributeInstanceOf(Connection::class, 'connection', $context);
        $this->assertAttributeSame(null, 'connectionFactory', $context);
    }

    public function testShouldCreateLazyContext()
    {
        $factory = new ManagerRegistryConnectionFactory($this->createManagerRegistryMock(), ['lazy' => true]);

        $context = $factory->createContext();

        $this->assertInstanceOf(DbalContext::class, $context);

        $this->assertAttributeEquals(null, 'connection', $context);
        $this->assertIsCallable($this->readAttribute($context, 'connectionFactory'));
    }

    /**
     * @return MockObject|ManagerRegistry
     */
    private function createManagerRegistryMock()
    {
        return $this->createMock(ManagerRegistry::class);
    }

    /**
     * @return MockObject|Connection
     */
    private function createConnectionMock()
    {
        return $this->createMock(Connection::class);
    }
}
