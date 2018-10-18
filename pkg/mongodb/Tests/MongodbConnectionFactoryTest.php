<?php

namespace Enqueue\Mongodb\Tests;

use Enqueue\Mongodb\MongodbConnectionFactory;
use Enqueue\Mongodb\MongodbContext;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\ConnectionFactory;
use PHPUnit\Framework\TestCase;

/**
 * @group mongodb
 */
class MongodbConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(ConnectionFactory::class, MongodbConnectionFactory::class);
    }

    public function testCouldBeConstructedWithEmptyConfiguration()
    {
        $params = [
            'dsn' => 'mongodb://127.0.0.1/',
            'dbname' => 'enqueue',
            'collection_name' => 'enqueue',
        ];

        $factory = new MongodbConnectionFactory();
        $this->assertAttributeEquals($params, 'config', $factory);
    }

    public function testCouldBeConstructedWithCustomConfiguration()
    {
        $params = [
            'dsn' => 'mongodb://127.0.0.3/',
            'dbname' => 'enqueue',
            'collection_name' => 'enqueue',
        ];

        $factory = new MongodbConnectionFactory($params);

        $this->assertAttributeEquals($params, 'config', $factory);
    }

    public function testShouldCreateContext()
    {
        $factory = new MongodbConnectionFactory();

        $context = $factory->createContext();

        $this->assertInstanceOf(MongodbContext::class, $context);
    }
}
