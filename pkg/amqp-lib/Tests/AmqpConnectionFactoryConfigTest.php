<?php

namespace Enqueue\AmqpLib\Tests;

use Enqueue\AmqpLib\AmqpConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

/**
 * The class contains the factory tests dedicated to configuration.
 */
class AmqpConnectionFactoryConfigTest extends TestCase
{
    use ClassExtensionTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string or null');

        new AmqpConnectionFactory(new \stdClass());
    }

    public function testThrowIfSchemeIsNotAmqp()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN scheme "http" is not supported. Could be "amqp" only.');

        new AmqpConnectionFactory('http://example.com');
    }

    public function testThrowIfDsnCouldNotBeParsed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Failed to parse DSN "amqp://:@/"');

        new AmqpConnectionFactory('amqp://:@/');
    }

    public function testThrowIfReceiveMenthodIsInvalid()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid "receive_method" option value "invalidMethod". It could be only "basic_get", "basic_consume"');

        new AmqpConnectionFactory(['receive_method' => 'invalidMethod']);
    }

    /**
     * @dataProvider provideConfigs
     *
     * @param mixed $config
     * @param mixed $expectedConfig
     */
    public function testShouldParseConfigurationAsExpected($config, $expectedConfig)
    {
        $factory = new AmqpConnectionFactory($config);

        $this->assertAttributeEquals($expectedConfig, 'config', $factory);
    }

    public static function provideConfigs()
    {
        yield [
            null,
            [
                'host' => 'localhost',
                'port' => 5672,
                'vhost' => '/',
                'user' => 'guest',
                'pass' => 'guest',
                'read_timeout' => 3,
                'write_timeout' => 3,
                'lazy' => true,
                'receive_method' => 'basic_get',
                'stream' => true,
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'keepalive' => false,
                'heartbeat' => 0,
                'connection_timeout' => 3.0,
                'read_write_timeout' => 3.0,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
            ],
        ];

        // some examples from Appendix A: Examples (https://www.rabbitmq.com/uri-spec.html)

        yield [
            'amqp+lib:',
            [
                'host' => 'localhost',
                'port' => 5672,
                'vhost' => '/',
                'user' => 'guest',
                'pass' => 'guest',
                'read_timeout' => 3,
                'write_timeout' => 3,
                'lazy' => true,
                'receive_method' => 'basic_get',
                'stream' => true,
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'keepalive' => false,
                'heartbeat' => 0,
                'connection_timeout' => 3.0,
                'read_write_timeout' => 3.0,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
            ],
        ];

        yield [
            'amqp+lib://user:pass@host:10000/vhost',
            [
                'host' => 'host',
                'port' => 10000,
                'vhost' => 'vhost',
                'user' => 'user',
                'pass' => 'pass',
                'read_timeout' => 3,
                'write_timeout' => 3,
                'lazy' => true,
                'receive_method' => 'basic_get',
                'stream' => true,
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'keepalive' => false,
                'heartbeat' => 0,
                'connection_timeout' => 3.0,
                'read_write_timeout' => 3.0,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
            ],
        ];

        yield [
            'amqp://user:pass@host:10000/vhost',
            [
                'host' => 'host',
                'port' => 10000,
                'vhost' => 'vhost',
                'user' => 'user',
                'pass' => 'pass',
                'read_timeout' => 3,
                'write_timeout' => 3,
                'lazy' => true,
                'receive_method' => 'basic_get',
                'stream' => true,
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'keepalive' => false,
                'heartbeat' => 0,
                'connection_timeout' => 3.0,
                'read_write_timeout' => 3.0,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
            ],
        ];

        yield [
            'amqp://user%61:%61pass@ho%61st:10000/v%2fhost',
            [
                'host' => 'hoast',
                'port' => 10000,
                'vhost' => 'v/host',
                'user' => 'usera',
                'pass' => 'apass',
                'read_timeout' => 3,
                'write_timeout' => 3,
                'lazy' => true,
                'receive_method' => 'basic_get',
                'stream' => true,
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'keepalive' => false,
                'heartbeat' => 0,
                'connection_timeout' => 3.0,
                'read_write_timeout' => 3.0,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
            ],
        ];

        yield [
            'amqp://',
            [
                'host' => 'localhost',
                'port' => 5672,
                'vhost' => '/',
                'user' => 'guest',
                'pass' => 'guest',
                'read_timeout' => 3,
                'write_timeout' => 3,
                'lazy' => true,
                'receive_method' => 'basic_get',
                'stream' => true,
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'keepalive' => false,
                'heartbeat' => 0,
                'connection_timeout' => 3.0,
                'read_write_timeout' => 3.0,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
            ],
        ];

        yield [
            'amqp://user:pass@host:10000/vhost?connection_timeout=2&lazy=',
            [
                'host' => 'host',
                'port' => 10000,
                'vhost' => 'vhost',
                'user' => 'user',
                'pass' => 'pass',
                'read_timeout' => 3,
                'write_timeout' => 3,
                'lazy' => '',
                'receive_method' => 'basic_get',
                'stream' => true,
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'keepalive' => false,
                'heartbeat' => 0,
                'connection_timeout' => '2',
                'read_write_timeout' => 3.0,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
            ],
        ];

        yield [
            [],
            [
                'host' => 'localhost',
                'port' => 5672,
                'vhost' => '/',
                'user' => 'guest',
                'pass' => 'guest',
                'read_timeout' => 3,
                'write_timeout' => 3,
                'lazy' => true,
                'receive_method' => 'basic_get',
                'stream' => true,
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'keepalive' => false,
                'heartbeat' => 0,
                'connection_timeout' => 3.0,
                'read_write_timeout' => 3.0,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
            ],
        ];

        yield [
            ['lazy' => false, 'host' => 'host'],
            [
                'host' => 'host',
                'port' => 5672,
                'vhost' => '/',
                'user' => 'guest',
                'pass' => 'guest',
                'read_timeout' => 3,
                'write_timeout' => 3,
                'lazy' => false,
                'receive_method' => 'basic_get',
                'stream' => true,
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'keepalive' => false,
                'heartbeat' => 0,
                'connection_timeout' => 3.0,
                'read_write_timeout' => 3.0,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
            ],
        ];

        yield [
            ['connection_timeout' => 123, 'read_write_timeout' => 321],
            [
                'host' => 'localhost',
                'port' => 5672,
                'vhost' => '/',
                'user' => 'guest',
                'pass' => 'guest',
                'read_timeout' => 3,
                'write_timeout' => 3,
                'lazy' => true,
                'receive_method' => 'basic_get',
                'stream' => true,
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'keepalive' => false,
                'heartbeat' => 0,
                'connection_timeout' => 123,
                'read_write_timeout' => 321,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
            ],
        ];

        yield [
            'amqp://user:pass@host:10000/vhost?connection_timeout=123&read_write_timeout=321',
            [
                'host' => 'host',
                'port' => 10000,
                'vhost' => 'vhost',
                'user' => 'user',
                'pass' => 'pass',
                'read_timeout' => 3,
                'write_timeout' => 3,
                'lazy' => true,
                'receive_method' => 'basic_get',
                'stream' => true,
                'insist' => false,
                'login_method' => 'AMQPLAIN',
                'login_response' => null,
                'locale' => 'en_US',
                'keepalive' => false,
                'heartbeat' => 0,
                'connection_timeout' => '123',
                'read_write_timeout' => '321',
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
            ],
        ];
    }
}
