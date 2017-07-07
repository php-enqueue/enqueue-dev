<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Redis\RedisContext;
use Enqueue\Test\ClassExtensionTrait;
use Interop\Queue\PsrConnectionFactory;

class RedisConnectionFactoryTest extends \PHPUnit\Framework\TestCase
{
    use ClassExtensionTrait;

    public function testShouldImplementConnectionFactoryInterface()
    {
        $this->assertClassImplements(PsrConnectionFactory::class, RedisConnectionFactory::class);
    }

    public function testCouldBeConstructedWithEmptyConfiguration()
    {
        $factory = new RedisConnectionFactory([]);

        $this->assertAttributeEquals([
            'host' => null,
            'port' => null,
            'timeout' => null,
            'reserved' => null,
            'retry_interval' => null,
            'persisted' => false,
            'lazy' => true,
            'vendor' => 'phpredis',
        ], 'config', $factory);
    }

    public function testCouldBeConstructedWithCustomConfiguration()
    {
        $factory = new RedisConnectionFactory(['host' => 'theCustomHost']);

        $this->assertAttributeEquals([
            'host' => 'theCustomHost',
            'port' => null,
            'timeout' => null,
            'reserved' => null,
            'retry_interval' => null,
            'persisted' => false,
            'lazy' => true,
            'vendor' => 'phpredis',
        ], 'config', $factory);
    }

    public function testShouldCreateLazyContext()
    {
        $factory = new RedisConnectionFactory(['lazy' => true]);

        $context = $factory->createContext();

        $this->assertInstanceOf(RedisContext::class, $context);

        $this->assertAttributeEquals(null, 'redis', $context);
        $this->assertInternalType('callable', $this->readAttribute($context, 'redisFactory'));
    }
}
