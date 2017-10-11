<?php

namespace Enqueue\AmqpExt\Tests;

use Enqueue\AmqpExt\AmqpConnectionFactory;
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
                'read_timeout' => null,
                'write_timeout' => null,
                'connect_timeout' => null,
                'persisted' => false,
                'lazy' => true,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
                'receive_method' => 'basic_get',
            ],
        ];

        // some examples from Appendix A: Examples (https://www.rabbitmq.com/uri-spec.html)

        yield [
            'amqp+ext:',
            [
                'host' => 'localhost',
                'port' => 5672,
                'vhost' => '/',
                'user' => 'guest',
                'pass' => 'guest',
                'read_timeout' => null,
                'write_timeout' => null,
                'connect_timeout' => null,
                'persisted' => false,
                'lazy' => true,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
                'receive_method' => 'basic_get',
            ],
        ];

        yield [
            'amqp+ext://user:pass@host:10000/vhost',
            [
                'host' => 'host',
                'port' => 10000,
                'vhost' => 'vhost',
                'user' => 'user',
                'pass' => 'pass',
                'read_timeout' => null,
                'write_timeout' => null,
                'connect_timeout' => null,
                'persisted' => false,
                'lazy' => true,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
                'receive_method' => 'basic_get',
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
                'read_timeout' => null,
                'write_timeout' => null,
                'connect_timeout' => null,
                'persisted' => false,
                'lazy' => true,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
                'receive_method' => 'basic_get',
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
                'read_timeout' => null,
                'write_timeout' => null,
                'connect_timeout' => null,
                'persisted' => false,
                'lazy' => true,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
                'receive_method' => 'basic_get',
            ],
        ];

        yield [
            'amqp:',
            [
                'host' => 'localhost',
                'port' => 5672,
                'vhost' => '/',
                'user' => 'guest',
                'pass' => 'guest',
                'read_timeout' => null,
                'write_timeout' => null,
                'connect_timeout' => null,
                'persisted' => false,
                'lazy' => true,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
                'receive_method' => 'basic_get',
            ],
        ];

        yield [
            'amqp://user:pass@host:10000/vhost?connect_timeout=2&lazy=',
            [
                'host' => 'host',
                'port' => 10000,
                'vhost' => 'vhost',
                'user' => 'user',
                'pass' => 'pass',
                'read_timeout' => null,
                'write_timeout' => null,
                'connect_timeout' => '2',
                'persisted' => false,
                'lazy' => '',
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
                'receive_method' => 'basic_get',
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
                'read_timeout' => null,
                'write_timeout' => null,
                'connect_timeout' => null,
                'persisted' => false,
                'lazy' => true,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
                'receive_method' => 'basic_get',
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
                'read_timeout' => null,
                'write_timeout' => null,
                'connect_timeout' => null,
                'persisted' => false,
                'lazy' => false,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
                'receive_method' => 'basic_get',
            ],
        ];

        yield [
            ['qos_prefetch_count' => 123, 'qos_prefetch_size' => 321],
            [
                'host' => 'localhost',
                'port' => 5672,
                'vhost' => '/',
                'user' => 'guest',
                'pass' => 'guest',
                'read_timeout' => null,
                'write_timeout' => null,
                'connect_timeout' => null,
                'persisted' => false,
                'lazy' => true,
                'qos_prefetch_count' => 123,
                'qos_prefetch_size' => 321,
                'qos_global' => false,
                'receive_method' => 'basic_get',
            ],
        ];

        yield [
            'amqp://user:pass@host:10000/vhost?qos_prefetch_count=123&qos_prefetch_size=321&qos_global=1',
            [
                'host' => 'host',
                'port' => '10000',
                'vhost' => 'vhost',
                'user' => 'user',
                'pass' => 'pass',
                'read_timeout' => null,
                'write_timeout' => null,
                'connect_timeout' => null,
                'persisted' => false,
                'lazy' => true,
                'qos_prefetch_size' => 321,
                'qos_prefetch_count' => 123,
                'qos_global' => true,
                'receive_method' => 'basic_get',
            ],
        ];
    }
}
