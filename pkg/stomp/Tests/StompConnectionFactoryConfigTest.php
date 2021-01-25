<?php

namespace Enqueue\Stomp\Tests;

use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use PHPUnit\Framework\TestCase;

/**
 * The class contains the factory tests dedicated to configuration.
 */
class StompConnectionFactoryConfigTest extends TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string or null');

        new StompConnectionFactory(new \stdClass());
    }

    public function testThrowIfSchemeIsNotStomp()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN is not supported. Must start with "stomp:".');

        new StompConnectionFactory('http://example.com');
    }

    public function testThrowIfDsnCouldNotBeParsed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The DSN is invalid.');

        new StompConnectionFactory('foo');
    }

    /**
     * @dataProvider provideConfigs
     *
     * @param mixed $config
     * @param mixed $expectedConfig
     */
    public function testShouldParseConfigurationAsExpected($config, $expectedConfig)
    {
        $factory = new StompConnectionFactory($config);

        $this->assertAttributeEquals($expectedConfig, 'config', $factory);
    }

    public static function provideConfigs()
    {
        yield [
            null,
            [
                'target' => 'rabbitmq',
                'host' => 'localhost',
                'port' => 61613,
                'login' => 'guest',
                'password' => 'guest',
                'vhost' => '/',
                'buffer_size' => 1000,
                'connection_timeout' => 1,
                'sync' => false,
                'lazy' => true,
                'ssl_on' => false,
                'write_timeout' => 3,
                'read_timeout' => 60,
                'send_heartbeat' => 0,
                'receive_heartbeat' => 0,
                'detect_transient_connections' => false,
            ],
        ];

        yield [
            'stomp:',
            [
                'target' => 'rabbitmq',
                'host' => 'localhost',
                'port' => 61613,
                'login' => 'guest',
                'password' => 'guest',
                'vhost' => '/',
                'buffer_size' => 1000,
                'connection_timeout' => 1,
                'sync' => false,
                'lazy' => true,
                'ssl_on' => false,
                'write_timeout' => 3,
                'read_timeout' => 60,
                'send_heartbeat' => 0,
                'receive_heartbeat' => 0,
                'detect_transient_connections' => false,
            ],
        ];

        yield [
            [],
            [
                'target' => 'rabbitmq',
                'host' => 'localhost',
                'port' => 61613,
                'login' => 'guest',
                'password' => 'guest',
                'vhost' => '/',
                'buffer_size' => 1000,
                'connection_timeout' => 1,
                'sync' => false,
                'lazy' => true,
                'ssl_on' => false,
                'write_timeout' => 3,
                'read_timeout' => 60,
                'send_heartbeat' => 0,
                'receive_heartbeat' => 0,
                'detect_transient_connections' => false,
            ],
        ];

        yield [
            'stomp://localhost:1234?foo=bar&lazy=0&sync=true',
            [
                'target' => 'rabbitmq',
                'host' => 'localhost',
                'port' => 1234,
                'login' => 'guest',
                'password' => 'guest',
                'vhost' => '/',
                'buffer_size' => 1000,
                'connection_timeout' => 1,
                'sync' => true,
                'lazy' => false,
                'foo' => 'bar',
                'ssl_on' => false,
                'write_timeout' => 3,
                'read_timeout' => 60,
                'send_heartbeat' => 0,
                'receive_heartbeat' => 0,
                'detect_transient_connections' => false,
            ],
        ];

        yield [
            'stomp+activemq://localhost:1234?foo=bar&lazy=0&sync=true',
            [
                'target' => 'activemq',
                'host' => 'localhost',
                'port' => 1234,
                'login' => 'guest',
                'password' => 'guest',
                'vhost' => '/',
                'buffer_size' => 1000,
                'connection_timeout' => 1,
                'sync' => true,
                'lazy' => false,
                'foo' => 'bar',
                'ssl_on' => false,
                'write_timeout' => 3,
                'read_timeout' => 60,
                'send_heartbeat' => 0,
                'receive_heartbeat' => 0,
                'detect_transient_connections' => false,
            ],
        ];

        yield [
            'stomp+rabbitmq://localhost:1234?foo=bar&lazy=0&sync=true',
            [
                'target' => 'rabbitmq',
                'host' => 'localhost',
                'port' => 1234,
                'login' => 'guest',
                'password' => 'guest',
                'vhost' => '/',
                'buffer_size' => 1000,
                'connection_timeout' => 1,
                'sync' => true,
                'lazy' => false,
                'foo' => 'bar',
                'ssl_on' => false,
                'write_timeout' => 3,
                'read_timeout' => 60,
                'send_heartbeat' => 0,
                'receive_heartbeat' => 0,
                'detect_transient_connections' => false,
            ],
        ];

        yield [
            ['dsn' => 'stomp://localhost:1234/theVhost?foo=bar&lazy=0&sync=true', 'baz' => 'bazVal', 'foo' => 'fooVal'],
            [
                'target' => 'rabbitmq',
                'host' => 'localhost',
                'port' => 1234,
                'login' => 'guest',
                'password' => 'guest',
                'vhost' => 'theVhost',
                'buffer_size' => 1000,
                'connection_timeout' => 1,
                'sync' => true,
                'lazy' => false,
                'foo' => 'bar',
                'ssl_on' => false,
                'baz' => 'bazVal',
                'write_timeout' => 3,
                'read_timeout' => 60,
                'send_heartbeat' => 0,
                'receive_heartbeat' => 0,
                'detect_transient_connections' => false,
            ],
        ];

        yield [
            ['dsn' => 'stomp:///%2f'],
            [
                'target' => 'rabbitmq',
                'host' => 'localhost',
                'port' => 61613,
                'login' => 'guest',
                'password' => 'guest',
                'vhost' => '/',
                'buffer_size' => 1000,
                'connection_timeout' => 1,
                'sync' => false,
                'lazy' => true,
                'ssl_on' => false,
                'write_timeout' => 3,
                'read_timeout' => 60,
                'send_heartbeat' => 0,
                'receive_heartbeat' => 0,
                'detect_transient_connections' => false,
            ],
        ];

        yield [
            ['host' => 'localhost', 'port' => 1234, 'foo' => 'bar'],
            [
                'target' => 'rabbitmq',
                'host' => 'localhost',
                'port' => 1234,
                'login' => 'guest',
                'password' => 'guest',
                'vhost' => '/',
                'buffer_size' => 1000,
                'connection_timeout' => 1,
                'sync' => false,
                'lazy' => true,
                'foo' => 'bar',
                'ssl_on' => false,
                'write_timeout' => 3,
                'read_timeout' => 60,
                'send_heartbeat' => 0,
                'receive_heartbeat' => 0,
                'detect_transient_connections' => false,
            ],
        ];
    }
}
