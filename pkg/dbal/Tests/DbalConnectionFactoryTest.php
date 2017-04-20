<?php
namespace Enqueue\Dbal\Tests;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\DbalContext;
use Enqueue\Psr\PsrConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;

class AmqpConnectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(PsrConnectionFactory::class, DbalConnectionFactory::class);
    }

    public function testCouldBeConstructedWithEmptyConfiguration()
    {
        $factory = new DbalConnectionFactory($this->createManagerRegistryMock(), []);

        $this->assertAttributeEquals([
            'lazy' => true,
            'connectionName' => null,
        ], 'config', $factory);
    }

    public function testCouldBeConstructedWithCustomConfiguration()
    {
        $factory = new DbalConnectionFactory($this->createManagerRegistryMock(), [
            'connectionName' => 'not-default',
            'lazy' => false,
        ]);

        $this->assertAttributeEquals([
            'lazy' => false,
            'connectionName' => 'not-default',
        ], 'config', $factory);
    }

    public function testShouldCreateContext()
    {
        $registry = $this->createManagerRegistryMock();
        $registry
            ->expects($this->once())
            ->method('getConnection')
            ->willReturn($connection = $this->createConnectionMock())
        ;

        $factory = new DbalConnectionFactory($registry, ['lazy' => false]);

        $context = $factory->createContext();

        $this->assertInstanceOf(DbalContext::class, $context);

        $this->assertAttributeSame($connection, 'connection', $context);
        $this->assertAttributeSame(null, 'connectionFactory', $context);
    }

    public function testShouldCreateLazyContext()
    {
        $factory = new DbalConnectionFactory($this->createManagerRegistryMock(), ['lazy' => true]);

        $context = $factory->createContext();

        $this->assertInstanceOf(DbalContext::class, $context);

        $this->assertAttributeEquals(null, 'connection', $context);
        $this->assertAttributeInternalType('callable', 'connectionFactory', $context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    private function createManagerRegistryMock()
    {
        return $this->createMock(ManagerRegistry::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    private function createConnectionMock()
    {
        return $this->createMock(Connection::class);
    }
}
