<?php

namespace Enqueue\Test;

use Enqueue\Redis\PhpRedis;
use Enqueue\Redis\PRedis;
use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Redis\RedisContext;
use PHPUnit\Framework\SkippedTestError;

trait RedisExtension
{
    private function buildPhpRedisContext(): RedisContext
    {
        if (false == getenv('PHPREDIS_DSN')) {
            throw new SkippedTestError('Functional tests are not allowed in this environment');
        }

        $config = getenv('PHPREDIS_DSN');

        $context = (new RedisConnectionFactory($config))->createContext();

        // guard
        $this->assertInstanceOf(PhpRedis::class, $context->getRedis());

        return $context;
    }

    private function buildPRedisContext(): RedisContext
    {
        if (false == getenv('PREDIS_DSN')) {
            throw new SkippedTestError('Functional tests are not allowed in this environment');
        }

        $config = getenv('PREDIS_DSN');

        $context = (new RedisConnectionFactory($config))->createContext();

        // guard
        $this->assertInstanceOf(PRedis::class, $context->getRedis());

        return $context;
    }
}
