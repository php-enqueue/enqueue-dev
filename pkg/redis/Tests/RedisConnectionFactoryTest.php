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
}
