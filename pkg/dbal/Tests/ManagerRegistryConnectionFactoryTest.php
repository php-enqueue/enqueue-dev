<?php
namespace Enqueue\Dbal\Tests;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;;
use Enqueue\Dbal\DbalContext;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use Enqueue\Psr\PsrConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;

class ManagerRegistryConnectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(PsrConnectionFactory::class, ManagerRegistryConnectionFactory::class);
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

        $factory = new ManagerRegistryConnectionFactory($registry, ['lazy' => false,]);

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
