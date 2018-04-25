<?php

namespace Enqueue\Mongodb\Tests;

use Enqueue\Mongodb\MongodbConnectionFactory;
use Enqueue\Mongodb\MongodbContext;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrConnectionFactory;

class MongodbConnectionFactoryTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(PsrConnectionFactory::class, MongodbConnectionFactory::class);
    }

    public function testCouldBeConstructedWithEmptyConfiguration()
    {
        $factory = new MongodbConnectionFactory();
        $this->assertAttributeEquals('mongodb://127.0.0.1/', 'uri', $factory);
        $this->assertAttributeEquals([], 'config', $factory);
        $this->assertAttributeEquals([], 'uriOptions', $factory);
        $this->assertAttributeEquals([], 'driverOptions', $factory);
    }

    public function testCouldBeConstructedWithCustomConfiguration()
    {
        $factory = new MongodbConnectionFactory('mongodb://127.0.0.3/', ['testValue' => 123], ['testValue' => 123], ['testValue' => 123]);

        $this->assertAttributeEquals('mongodb://127.0.0.3/', 'uri', $factory);
        $this->assertAttributeEquals(['testValue' => 123], 'config', $factory);
        $this->assertAttributeEquals(['testValue' => 123], 'uriOptions', $factory);
        $this->assertAttributeEquals(['testValue' => 123], 'driverOptions', $factory);
    }

    public function testShouldCreateContext()
    {
        $factory = new MongodbConnectionFactory();

        $context = $factory->createContext();

        $this->assertInstanceOf(MongodbContext::class, $context);
    }
}
