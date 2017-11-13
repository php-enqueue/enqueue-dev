<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

/**
 * The class contains the factory tests dedicated to configuration.
 */
class RedisConnectionFactoryConfigTest extends TestCase
{
    use ClassExtensionTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string or null');

        new RedisConnectionFactory(new \stdClass());
    }

    public function testThrowIfSchemeIsNotAmqp()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN "http://example.com" is not supported. Must start with "redis:".');

        new RedisConnectionFactory('http://example.com');
    }

    public function testThrowIfDsnCouldNotBeParsed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Failed to parse DSN "redis://:@/"');

        new RedisConnectionFactory('redis://:@/');
    }

    public function testThrowIfVendorIsInvalid()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Unsupported redis vendor given. It must be either "predis", "phpredis". Got "invalidVendor"');

        new RedisConnectionFactory(['vendor' => 'invalidVendor']);
    }

    /**
     * @dataProvider provideConfigs
     *
     * @param mixed $config
     * @param mixed $expectedConfig
     */
    public function testShouldParseConfigurationAsExpected($config, $expectedConfig)
    {
        $factory = new RedisConnectionFactory($config);

        $this->assertAttributeEquals($expectedConfig, 'config', $factory);
    }

    public static function provideConfigs()
    {
        yield [
            null,
            [
                'host' => 'localhost',
                'port' => 6379,
                'timeout' => null,
                'reserved' => null,
                'retry_interval' => null,
                'vendor' => 'phpredis',
                'persisted' => false,
                'lazy' => true,
                'database' => 0,
            ],
        ];

        yield [
            'redis:',
            [
                'host' => 'localhost',
                'port' => 6379,
                'timeout' => null,
                'reserved' => null,
                'retry_interval' => null,
                'vendor' => 'phpredis',
                'persisted' => false,
                'lazy' => true,
                'database' => 0,
            ],
        ];

        yield [
            [],
            [
                'host' => 'localhost',
                'port' => 6379,
                'timeout' => null,
                'reserved' => null,
                'retry_interval' => null,
                'vendor' => 'phpredis',
                'persisted' => false,
                'lazy' => true,
                'database' => 0,
            ],
        ];

        yield [
            'redis://localhost:1234?foo=bar&lazy=0&persisted=true&database=5',
            [
                'host' => 'localhost',
                'port' => 1234,
                'timeout' => null,
                'reserved' => null,
                'retry_interval' => null,
                'vendor' => 'phpredis',
                'persisted' => true,
                'lazy' => false,
                'foo' => 'bar',
                'database' => 5,
            ],
        ];

        yield [
            ['host' => 'localhost', 'port' => 1234, 'foo' => 'bar'],
            [
                'host' => 'localhost',
                'port' => 1234,
                'timeout' => null,
                'reserved' => null,
                'retry_interval' => null,
                'vendor' => 'phpredis',
                'persisted' => false,
                'lazy' => true,
                'foo' => 'bar',
                'database' => 0,
            ],
        ];
    }
}
