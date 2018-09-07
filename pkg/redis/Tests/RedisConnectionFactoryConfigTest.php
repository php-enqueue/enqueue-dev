<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Redis\Redis;
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
        $this->expectExceptionMessage('The given DSN "http://example.com" is not supported. Must start with "redis:" or "rediss:".');

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
        $this->expectExceptionMessage('Unsupported redis vendor given. It must be either "predis", "phpredis", "custom". Got "invalidVendor"');

        new RedisConnectionFactory(['vendor' => 'invalidVendor']);
    }

    public function testCouldBeCreatedWithRedisInstance()
    {
        $redisMock = $this->createMock(Redis::class);

        $factory = new RedisConnectionFactory($redisMock);
        $this->assertAttributeSame($redisMock, 'redis', $factory);

        $context = $factory->createContext();
        $this->assertSame($redisMock, $context->getRedis());
    }

    public function testThrowIfRedissConnectionUsedWithPhpRedisExtension()
    {
        $factory = new RedisConnectionFactory('rediss:?vendor=phpredis');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The phpredis extension does not support secured connections. Try to use predis library as vendor.');
        $factory->createContext();
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
                'scheme' => 'redis',
                'port' => 6379,
                'timeout' => null,
                'reserved' => null,
                'retry_interval' => null,
                'vendor' => 'phpredis',
                'persisted' => false,
                'lazy' => true,
                'database' => 0,
                'redis' => null,
            ],
        ];

        yield [
            'redis:',
            [
                'host' => 'localhost',
                'scheme' => 'redis',
                'port' => 6379,
                'timeout' => null,
                'reserved' => null,
                'retry_interval' => null,
                'vendor' => 'phpredis',
                'persisted' => false,
                'lazy' => true,
                'database' => 0,
                'redis' => null,
            ],
        ];

        yield [
            [],
            [
                'host' => 'localhost',
                'scheme' => 'redis',
                'port' => 6379,
                'timeout' => null,
                'reserved' => null,
                'retry_interval' => null,
                'vendor' => 'phpredis',
                'persisted' => false,
                'lazy' => true,
                'database' => 0,
                'redis' => null,
            ],
        ];

        yield [
            'redis://localhost:1234?foo=bar&lazy=0&persisted=true&database=5',
            [
                'host' => 'localhost',
                'scheme' => 'redis',
                'port' => 1234,
                'timeout' => null,
                'reserved' => null,
                'retry_interval' => null,
                'vendor' => 'phpredis',
                'persisted' => true,
                'lazy' => false,
                'foo' => 'bar',
                'database' => 5,
                'redis' => null,
            ],
        ];

        //check normal redis connection for predis library
        yield [
            'redis://localhost:1234?foo=bar&lazy=0&vendor=predis',
            [
                'host' => 'localhost',
                'scheme' => 'redis',
                'port' => 1234,
                'timeout' => null,
                'reserved' => null,
                'retry_interval' => null,
                'vendor' => 'predis',
                'persisted' => false,
                'lazy' => false,
                'foo' => 'bar',
                'database' => 0,
                'redis' => null,
            ],
        ];

        //check tls connection for predis library
        yield [
            'rediss://localhost:1234?foo=bar&lazy=0&vendor=predis',
            [
                'host' => 'localhost',
                'scheme' => 'rediss',
                'port' => 1234,
                'timeout' => null,
                'reserved' => null,
                'retry_interval' => null,
                'vendor' => 'predis',
                'persisted' => false,
                'lazy' => false,
                'foo' => 'bar',
                'database' => 0,
                'redis' => null,
            ],
        ];

        yield [
            ['host' => 'localhost', 'port' => 1234, 'foo' => 'bar'],
            [
                'host' => 'localhost',
                'scheme' => 'redis',
                'port' => 1234,
                'timeout' => null,
                'reserved' => null,
                'retry_interval' => null,
                'vendor' => 'phpredis',
                'persisted' => false,
                'lazy' => true,
                'foo' => 'bar',
                'database' => 0,
                'redis' => null,
            ],
        ];

        // heroku redis
        yield [
            'redis://h:asdfqwer1234asdf@ec2-111-1-1-1.compute-1.amazonaws.com:111',
            [
                'host' => 'ec2-111-1-1-1.compute-1.amazonaws.com',
                'scheme' => 'redis',
                'port' => 111,
                'timeout' => null,
                'reserved' => null,
                'retry_interval' => null,
                'vendor' => 'phpredis',
                'persisted' => false,
                'lazy' => false,
                'database' => 0,
                'redis' => null,
                'user' => 'h',
                'pass' => 'asdfqwer1234asdf',
            ],
        ];

        // from predis doc

        yield [
            'tls://127.0.0.1?ssl[cafile]=private.pem&ssl[verify_peer]=1',
            [
                'host' => 'ec2-111-1-1-1.compute-1.amazonaws.com',
                'scheme' => 'redis',
                'port' => 111,
                'timeout' => null,
                'reserved' => null,
                'retry_interval' => null,
                'vendor' => 'phpredis',
                'persisted' => false,
                'lazy' => false,
                'database' => 0,
                'redis' => null,
                'user' => 'h',
                'pass' => 'asdfqwer1234asdf',
            ],
        ];
    }
}
