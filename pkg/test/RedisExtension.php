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
        if (false == getenv('SYMFONY__REDIS__HOST')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        $config = [
            'host' => getenv('SYMFONY__REDIS__HOST'),
            'port' => getenv('SYMFONY__REDIS__PORT'),
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
        if (false == getenv('SYMFONY__REDIS__HOST')) {
            throw new \PHPUnit_Framework_SkippedTestError('Functional tests are not allowed in this environment');
        }

        $config = [
            'host' => getenv('SYMFONY__REDIS__HOST'),
            'port' => getenv('SYMFONY__REDIS__PORT'),
            'vendor' => 'predis',
            'lazy' => false,
        ];

        return (new RedisConnectionFactory($config))->createContext();
    }
}
