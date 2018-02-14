<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Redis\RedisContext;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrConnectionFactory;
use PHPUnit\Framework\TestCase;

class RedisConnectionFactoryTest extends TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(PsrConnectionFactory::class, RedisConnectionFactory::class);
    }

    public function testShouldCreateLazyContext()
    {
        $factory = new RedisConnectionFactory(['lazy' => true]);

        $context = $factory->createContext();

        $this->assertInstanceOf(RedisContext::class, $context);

        $this->assertAttributeEquals(null, 'redis', $context);
        $this->assertInternalType('callable', $this->readAttribute($context, 'redisFactory'));
    }

    public function testShouldAcceptOptionsForPredisClient()
    {
        $predisClientMock = $this->getMockBuilder(\Predis\Client::class)
                          ->setMethods(['createConnection'])
                          ->getMock();

        $factory = new RedisConnectionFactory(['vendor' => 'predis', 'options' => ['foo' => 'bar']]);

        $context = $factory->createContext();
        $predis = $context->getRedis();

        $reflector = new \ReflectionClass($predis);
        $reflector_property = $reflector->getProperty('redis');
        $reflector_property->setAccessible(true);
        $reflectorRedis = $reflector_property->getValue($predis);
        $predisOptions = $reflectorRedis->getOptions();

        $this->assertTrue($predisOptions->defined('foo'));
        $this->assertEquals('bar', $predisOptions->foo);
    }
}
