<?php

namespace Enqueue\Dbal\Tests;

use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\DbalContext;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use Interop\Queue\ConnectionFactory;
use PHPUnit\Framework\TestCase;

class DbalConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(ConnectionFactory::class, DbalConnectionFactory::class);
    }

    public function testShouldCreateLazyContext()
    {
        $factory = new DbalConnectionFactory(['lazy' => true]);

        $context = $factory->createContext();

        $this->assertInstanceOf(DbalContext::class, $context);

        $this->assertAttributeEquals(null, 'connection', $context);
        $this->assertIsCallable($this->readAttribute($context, 'connectionFactory'));
    }

    public function testShouldParseGenericDSN()
    {
        $factory = new DbalConnectionFactory('pgsql+pdo://foo@bar');

        $context = $factory->createContext();

        $this->assertInstanceOf(DbalContext::class, $context);

        $config = $context->getConfig();
        $this->assertArrayHasKey('connection', $config);
        $this->assertArrayHasKey('url', $config['connection']);
        $this->assertEquals('pdo_pgsql://foo@bar', $config['connection']['url']);
    }

    public function testShouldParseSqliteAbsolutePathDSN()
    {
        $factory = new DbalConnectionFactory('sqlite+pdo:////tmp/some.sq3');

        $context = $factory->createContext();

        $this->assertInstanceOf(DbalContext::class, $context);

        $config = $context->getConfig();
        $this->assertArrayHasKey('connection', $config);
        $this->assertArrayHasKey('url', $config['connection']);
        $this->assertEquals('pdo_sqlite:////tmp/some.sq3', $config['connection']['url']);
    }
}
