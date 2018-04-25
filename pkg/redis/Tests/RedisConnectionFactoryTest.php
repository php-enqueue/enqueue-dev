<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Redis\Redis;
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

    public function testShouldThrowIfVendorIsCustomButRedisInstanceNotSet()
    {
        $factory = new RedisConnectionFactory([
            'vendor' => 'custom',
            'redis' => null,
            'lazy' => false,
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The redis option should be set if vendor is custom.');
        $factory->createContext();
    }

    public function testShouldThrowIfVendorIsCustomButRedisIsNotInstanceOfRedis()
    {
        $factory = new RedisConnectionFactory([
            'vendor' => 'custom',
            'redis' => new \stdClass(),
            'lazy' => false,
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The redis option should be instance of "Enqueue\Redis\Redis".');
        $factory->createContext();
    }

    public function testShouldUseCustomRedisInstance()
    {
        $redisMock = $this->createMock(Redis::class);

        $factory = new RedisConnectionFactory([
            'vendor' => 'custom',
            'redis' => $redisMock,
            'lazy' => false,
        ]);

        $context = $factory->createContext();

        $this->assertAttributeSame($redisMock, 'redis', $context);
    }
}
