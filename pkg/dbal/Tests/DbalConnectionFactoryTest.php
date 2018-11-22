<?php

namespace Enqueue\Dbal\Tests;

use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\DbalContext;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\ConnectionFactory;
use PHPUnit\Framework\TestCase;

class DbalConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;

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
        $this->assertAttributeInternalType('callable', 'connectionFactory', $context);
    }
}
