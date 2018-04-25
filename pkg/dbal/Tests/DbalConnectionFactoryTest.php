<?php

namespace Enqueue\Dbal\Tests;

use Doctrine\DBAL\Connection;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Dbal\DbalContext;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrConnectionFactory;

class DbalConnectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(PsrConnectionFactory::class, DbalConnectionFactory::class);
    }

    public function testCouldBeConstructedWithEmptyConfiguration()
    {
        $factory = new DbalConnectionFactory();

        $this->assertAttributeEquals([
            'lazy' => true,
            'connection' => ['url' => 'mysql://root@localhost'],
        ], 'config', $factory);
    }

    public function testCouldBeConstructedWithCustomConfiguration()
    {
        $factory = new DbalConnectionFactory([
            'connection' => [
                'dbname' => 'theDbName',
            ],
            'lazy' => false,
        ]);

        $this->assertAttributeEquals([
            'lazy' => false,
            'connection' => [
                'dbname' => 'theDbName',
            ],
        ], 'config', $factory);
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
