<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Redis\PRedis;
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

    public function testShouldUsePredisInstanceByDefault()
    {
        $factory = new RedisConnectionFactory('redis:?lazy=1');

        $context = $factory->createContext();
        $this->assertInstanceOf(PRedis::class, $context->getRedis());
    }

    public function testShouldUsePredisInstanceSetExplicitly()
    {
        $factory = new RedisConnectionFactory('redis+predis:?lazy=1');

        $context = $factory->createContext();
        $this->assertInstanceOf(PRedis::class, $context->getRedis());
    }
}
