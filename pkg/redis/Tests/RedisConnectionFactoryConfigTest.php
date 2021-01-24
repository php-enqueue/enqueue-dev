<?php

namespace Enqueue\Redis\Tests;

use Enqueue\Redis\Redis;
use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use PHPUnit\Framework\TestCase;

/**
 * The class contains the factory tests dedicated to configuration.
 */
class RedisConnectionFactoryConfigTest extends TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string, null or instance of Enqueue\Redis\Redis');

        new RedisConnectionFactory(new \stdClass());
    }

    public function testThrowIfSchemeIsNotRedis()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given scheme protocol "http" is not supported. It must be one of "redis", "rediss", "tcp", "tls", "unix"');

        new RedisConnectionFactory('http://example.com');
    }

    public function testThrowIfDsnCouldNotBeParsed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The DSN is invalid.');

        new RedisConnectionFactory('foo');
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
        $factory = new RedisConnectionFactory('rediss+phpredis:?lazy=0');

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given scheme protocol "rediss" is not supported by php extension. It must be one of "redis", "tcp", "unix"');
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
                'host' => '127.0.0.1',
                'scheme' => 'redis',
                'port' => 6379,
                'timeout' => 5.,
                'database' => null,
                'password' => null,
                'scheme_extensions' => [],
                'path' => null,
                'async' => false,
                'persistent' => false,
                'lazy' => true,
                'read_write_timeout' => null,
                'predis_options' => null,
                'ssl' => null,
                'redelivery_delay' => 300,
            ],
        ];

        yield [
            'redis:',
            [
                'host' => '127.0.0.1',
                'scheme' => 'redis',
                'port' => 6379,
                'timeout' => 5.,
                'database' => null,
                'password' => null,
                'scheme_extensions' => [],
                'path' => null,
                'async' => false,
                'persistent' => false,
                'lazy' => true,
                'read_write_timeout' => null,
                'predis_options' => null,
                'ssl' => null,
                'redelivery_delay' => 300,
            ],
        ];

        yield [
            [],
            [
                'host' => '127.0.0.1',
                'scheme' => 'redis',
                'port' => 6379,
                'timeout' => 5.,
                'database' => null,
                'password' => null,
                'scheme_extensions' => [],
                'path' => null,
                'async' => false,
                'persistent' => false,
                'lazy' => true,
                'read_write_timeout' => null,
                'predis_options' => null,
                'ssl' => null,
                'redelivery_delay' => 300,
            ],
        ];

        yield [
            'unix:/path/to/redis.sock?foo=bar&database=5',
            [
                'host' => '127.0.0.1',
                'scheme' => 'unix',
                'port' => 6379,
                'timeout' => 5.,
                'database' => 5,
                'password' => null,
                'scheme_extensions' => [],
                'path' => '/path/to/redis.sock',
                'async' => false,
                'persistent' => false,
                'lazy' => true,
                'read_write_timeout' => null,
                'predis_options' => null,
                'ssl' => null,
                'foo' => 'bar',
                'redelivery_delay' => 300,
            ],
        ];

        yield [
            ['dsn' => 'redis://expectedHost:1234/5', 'host' => 'shouldBeOverwrittenHost', 'foo' => 'bar'],
            [
                'host' => 'expectedHost',
                'scheme' => 'redis',
                'port' => 1234,
                'timeout' => 5.,
                'database' => 5,
                'password' => null,
                'scheme_extensions' => [],
                'path' => '/5',
                'async' => false,
                'persistent' => false,
                'lazy' => true,
                'read_write_timeout' => null,
                'predis_options' => null,
                'ssl' => null,
                'foo' => 'bar',
                'redelivery_delay' => 300,
            ],
        ];

        yield [
            'redis+predis://localhost:1234/5?foo=bar&persistent=true',
            [
                'host' => 'localhost',
                'scheme' => 'redis',
                'port' => 1234,
                'timeout' => 5.,
                'database' => 5,
                'password' => null,
                'scheme_extensions' => ['predis'],
                'path' => '/5',
                'async' => false,
                'persistent' => true,
                'lazy' => true,
                'read_write_timeout' => null,
                'predis_options' => null,
                'ssl' => null,
                'foo' => 'bar',
                'redelivery_delay' => 300,
            ],
        ];

        //check normal redis connection for php redis extension
        yield [
            'redis+phpredis://localhost:1234?foo=bar',
            [
                'host' => 'localhost',
                'scheme' => 'redis',
                'port' => 1234,
                'timeout' => 5.,
                'database' => null,
                'password' => null,
                'scheme_extensions' => ['phpredis'],
                'path' => null,
                'async' => false,
                'persistent' => false,
                'lazy' => true,
                'read_write_timeout' => null,
                'predis_options' => null,
                'ssl' => null,
                'foo' => 'bar',
                'redelivery_delay' => 300,
            ],
        ];

        //check normal redis connection for predis library
        yield [
            'redis+predis://localhost:1234?foo=bar',
            [
                'host' => 'localhost',
                'scheme' => 'redis',
                'port' => 1234,
                'timeout' => 5.,
                'database' => null,
                'password' => null,
                'scheme_extensions' => ['predis'],
                'path' => null,
                'async' => false,
                'persistent' => false,
                'lazy' => true,
                'read_write_timeout' => null,
                'predis_options' => null,
                'ssl' => null,
                'foo' => 'bar',
                'redelivery_delay' => 300,
            ],
        ];

        //check tls connection for predis library
        yield [
            'rediss+predis://localhost:1234?foo=bar&async=1',
            [
                'host' => 'localhost',
                'scheme' => 'rediss',
                'port' => 1234,
                'timeout' => 5.,
                'database' => null,
                'password' => null,
                'scheme_extensions' => ['predis'],
                'path' => null,
                'async' => true,
                'persistent' => false,
                'lazy' => true,
                'read_write_timeout' => null,
                'predis_options' => null,
                'ssl' => null,
                'foo' => 'bar',
                'redelivery_delay' => 300,
            ],
        ];

        yield [
            ['host' => 'localhost', 'port' => 1234, 'foo' => 'bar'],
            [
                'host' => 'localhost',
                'scheme' => 'redis',
                'port' => 1234,
                'timeout' => 5.,
                'database' => null,
                'password' => null,
                'scheme_extensions' => [],
                'path' => null,
                'async' => false,
                'persistent' => false,
                'lazy' => true,
                'read_write_timeout' => null,
                'predis_options' => null,
                'ssl' => null,
                'foo' => 'bar',
                'redelivery_delay' => 300,
            ],
        ];

        // heroku redis
        yield [
            'redis://h:asdfqwer1234asdf@ec2-111-1-1-1.compute-1.amazonaws.com:111',
            [
                'host' => 'ec2-111-1-1-1.compute-1.amazonaws.com',
                'scheme' => 'redis',
                'port' => 111,
                'timeout' => 5.,
                'database' => null,
                'password' => 'asdfqwer1234asdf',
                'scheme_extensions' => [],
                'path' => null,
                'async' => false,
                'persistent' => false,
                'lazy' => true,
                'read_write_timeout' => null,
                'predis_options' => null,
                'ssl' => null,
                'redelivery_delay' => 300,
            ],
        ];

        // password as user
        yield [
            'redis://asdfqwer1234asdf@foo',
            [
                'host' => 'foo',
                'scheme' => 'redis',
                'port' => 6379,
                'timeout' => 5.,
                'database' => null,
                'password' => 'asdfqwer1234asdf',
                'scheme_extensions' => [],
                'path' => null,
                'async' => false,
                'persistent' => false,
                'lazy' => true,
                'read_write_timeout' => null,
                'predis_options' => null,
                'ssl' => null,
                'redelivery_delay' => 300,
            ],
        ];

        // password as query parameter
        yield [
            'redis:?password=asdfqwer1234asdf',
            [
                'host' => '127.0.0.1',
                'scheme' => 'redis',
                'port' => 6379,
                'timeout' => 5.,
                'database' => null,
                'password' => 'asdfqwer1234asdf',
                'scheme_extensions' => [],
                'path' => null,
                'async' => false,
                'persistent' => false,
                'lazy' => true,
                'read_write_timeout' => null,
                'predis_options' => null,
                'ssl' => null,
                'redelivery_delay' => 300,
            ],
        ];

        // from predis doc

        yield [
            'tls://127.0.0.1?ssl[cafile]=private.pem&ssl[verify_peer]=1',
            [
                'host' => '127.0.0.1',
                'scheme' => 'tls',
                'port' => 6379,
                'timeout' => 5.,
                'database' => null,
                'scheme_extensions' => [],
                'path' => null,
                'async' => false,
                'persistent' => false,
                'lazy' => true,
                'read_write_timeout' => null,
                'predis_options' => null,
                'password' => null,
                'ssl' => [
                    'cafile' => 'private.pem',
                    'verify_peer' => '1',
                ],
                'redelivery_delay' => 300,
            ],
        ];
    }
}
