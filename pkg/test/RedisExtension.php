<?php

namespace Enqueue\Test;

use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Redis\RedisContext;

trait RedisExtension
{
    /**
     * @return RedisContext
     */
    private function buildPhpRedisContext()
    {
        if (false == getenv('REDIS_HOST')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        $config = [
            'host' => getenv('REDIS_HOST'),
            'port' => getenv('REDIS_PORT'),
            'vendor' => 'phpredis',
            'lazy' => false,
        ];

        return (new RedisConnectionFactory($config))->createContext();
    }

    /**
     * @return RedisContext
     */
    private function buildPRedisContext()
    {
        if (false == getenv('REDIS_HOST')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        $config = [
            'host' => getenv('REDIS_HOST'),
            'port' => getenv('REDIS_PORT'),
            'vendor' => 'predis',
            'lazy' => false,
        ];

        return (new RedisConnectionFactory($config))->createContext();
    }
}
