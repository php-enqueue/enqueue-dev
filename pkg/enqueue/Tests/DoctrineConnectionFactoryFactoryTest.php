<?php

declare(strict_types=1);

namespace Enqueue\Tests;

use Doctrine\Persistence\ManagerRegistry;
use Enqueue\ConnectionFactoryFactoryInterface;
use Enqueue\Dbal\ManagerRegistryConnectionFactory;
use Enqueue\Doctrine\DoctrineConnectionFactoryFactory;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;

class DoctrineConnectionFactoryFactoryTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var ManagerRegistry|\Prophecy\Prophecy\ObjectProphecy
     */
    private $registry;
    /**
     * @var ConnectionFactoryFactoryInterface|\Prophecy\Prophecy\ObjectProphecy
     */
    private $fallbackFactory;
    /**
     * @var DoctrineConnectionFactoryFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->registry = $this->prophesize(ManagerRegistry::class);
        $this->fallbackFactory = $this->prophesize(ConnectionFactoryFactoryInterface::class);

        $this->factory = new DoctrineConnectionFactoryFactory($this->registry->reveal(), $this->fallbackFactory->reveal());
    }

    public function testCreateWithoutArray()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The config must be either array or DSN string.');

        $this->factory->create(true);
    }

    public function testCreateWithoutDsn()
    {
        $this->expectExceptionMessage(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The config must have dsn key set.');

        $this->factory->create(['foo' => 'bar']);
    }

    public function testCreateWithDoctrineSchema()
    {
        $this->assertInstanceOf(
            ManagerRegistryConnectionFactory::class,
            $this->factory->create('doctrine://localhost:3306')
        );
    }

    public function testCreateFallback()
    {
        $this->fallbackFactory
            ->create(['dsn' => 'fallback://'])
            ->shouldBeCalled();

        $this->factory->create(['dsn' => 'fallback://']);
    }
}
