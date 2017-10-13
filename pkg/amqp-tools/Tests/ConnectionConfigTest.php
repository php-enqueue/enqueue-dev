<?php

namespace Enqueue\AmqpTools\Tests;

use Enqueue\AmqpTools\ConnectionConfig;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

/**
 * The class contains the factory tests dedicated to configuration.
 */
class ConnectionConfigTest extends TestCase
{
    use ClassExtensionTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string or null');

        (new ConnectionConfig(new \stdClass()))->parse();
    }

    public function testThrowIfSchemeIsNotSupported()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN scheme "http" is not supported. Could be one of "amqp" only.');

        (new ConnectionConfig('http://example.com'))->parse();
    }

    public function testThrowIfSchemeIsNotSupportedIncludingAdditionalSupportedSchemes()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN scheme "http" is not supported. Could be one of "amqp", "amqp+foo" only.');

        (new ConnectionConfig('http://example.com'))
            ->addSupportedSchemes('amqp+foo')
            ->parse()
        ;
    }

    public function testThrowIfDsnCouldNotBeParsed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Failed to parse DSN "amqp://:@/"');

        (new ConnectionConfig('amqp://:@/'))->parse();
    }

    public function testShouldParseEmptyDsnWithDriverSet()
    {
        $config = (new ConnectionConfig('amqp+foo:'))
            ->addSupportedSchemes('amqp+foo')
            ->parse()
        ;

        $this->assertEquals([
            'host' => 'localhost',
            'port' => 5672,
            'vhost' => '/',
            'user' => 'guest',
            'pass' => 'guest',
            'read_timeout' => 3.,
            'write_timeout' => 3.,
            'connection_timeout' => 3.,
            'persisted' => true,
            'lazy' => true,
            'qos_prefetch_size' => 0,
            'qos_prefetch_count' => 1,
            'qos_global' => false,
            'heartbeat' => 0.0,
        ], $config->getConfig());
    }

    public function testShouldParseCustomDsnWithDriverSet()
    {
        $config = (new ConnectionConfig('amqp+foo://user:pass@host:10000/vhost'))
            ->addSupportedSchemes('amqp+foo')
            ->parse()
        ;

        $this->assertEquals([
            'host' => 'host',
            'port' => 10000,
            'vhost' => 'vhost',
            'user' => 'user',
            'pass' => 'pass',
            'read_timeout' => 3.,
            'write_timeout' => 3.,
            'connection_timeout' => 3.,
            'persisted' => true,
            'lazy' => true,
            'qos_prefetch_size' => 0,
            'qos_prefetch_count' => 1,
            'qos_global' => false,
            'heartbeat' => 0.0,
        ], $config->getConfig());
    }

    /**
     * @dataProvider provideConfigs
     *
     * @param mixed $config
     * @param mixed $expectedConfig
     */
    public function testShouldParseConfigurationAsExpected($config, $expectedConfig)
    {
        $config = new ConnectionConfig($config);
        $config->parse();

        $this->assertEquals($expectedConfig, $config->getConfig());
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
                'read_timeout' => 3.,
                'write_timeout' => 3.,
                'connection_timeout' => 3.,
                'persisted' => true,
                'lazy' => true,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
                'heartbeat' => 0.0,
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
                'read_timeout' => 3.,
                'write_timeout' => 3.,
                'connection_timeout' => 3.,
                'persisted' => true,
                'lazy' => true,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
                'heartbeat' => 0.0,
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
                'read_timeout' => 3.,
                'write_timeout' => 3.,
                'connection_timeout' => 3.,
                'persisted' => true,
                'lazy' => true,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
                'heartbeat' => 0.0,
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
                'read_timeout' => 3.,
                'write_timeout' => 3.,
                'connection_timeout' => 3.,
                'persisted' => true,
                'lazy' => true,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
                'heartbeat' => 0.0,
            ],
        ];

        yield [
            'amqp://user:pass@host:10000/vhost?connection_timeout=20&write_timeout=4&read_timeout=-4&heartbeat=23.3',
            [
                'host' => 'host',
                'port' => 10000,
                'vhost' => 'vhost',
                'user' => 'user',
                'pass' => 'pass',
                'read_timeout' => 0.,
                'write_timeout' => 4,
                'connection_timeout' => 20.,
                'persisted' => true,
                'lazy' => true,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
                'heartbeat' => 23.3,
            ],
        ];

        yield [
            'amqp://user:pass@host:10000/vhost?persisted=0&lazy=&qos_global=true',
            [
                'host' => 'host',
                'port' => 10000,
                'vhost' => 'vhost',
                'user' => 'user',
                'pass' => 'pass',
                'read_timeout' => 3.,
                'write_timeout' => 3.,
                'connection_timeout' => 3.,
                'persisted' => false,
                'lazy' => false,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => true,
                'heartbeat' => 0.0,
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
                'read_timeout' => 3.,
                'write_timeout' => 3.,
                'connection_timeout' => 3.,
                'persisted' => true,
                'lazy' => true,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => false,
                'heartbeat' => 0.0,
            ],
        ];

        yield [
            ['lazy' => false, 'persisted' => 0, 'qos_global' => 1],
            [
                'host' => 'localhost',
                'port' => 5672,
                'vhost' => '/',
                'user' => 'guest',
                'pass' => 'guest',
                'read_timeout' => 3.,
                'write_timeout' => 3.,
                'connection_timeout' => 3.,
                'persisted' => false,
                'lazy' => false,
                'qos_prefetch_size' => 0,
                'qos_prefetch_count' => 1,
                'qos_global' => true,
                'heartbeat' => 0.0,
            ],
        ];

        yield [
            ['qos_prefetch_count' => 123, 'qos_prefetch_size' => -2],
            [
                'host' => 'localhost',
                'port' => 5672,
                'vhost' => '/',
                'user' => 'guest',
                'pass' => 'guest',
                'read_timeout' => 3.,
                'write_timeout' => 3.,
                'connection_timeout' => 3.,
                'persisted' => true,
                'lazy' => true,
                'qos_prefetch_count' => 123,
                'qos_prefetch_size' => 0,
                'qos_global' => false,
                'heartbeat' => 0.0,
            ],
        ];

        yield [
            'amqp://user:pass@host:10000/vhost?qos_prefetch_count=123&qos_prefetch_size=-2',
            [
                'host' => 'host',
                'port' => 10000,
                'vhost' => 'vhost',
                'user' => 'user',
                'pass' => 'pass',
                'read_timeout' => 3.,
                'write_timeout' => 3.,
                'connection_timeout' => 3.,
                'persisted' => true,
                'lazy' => true,
                'qos_prefetch_count' => 123,
                'qos_prefetch_size' => 0,
                'qos_global' => false,
                'heartbeat' => 0.0,
            ],
        ];

        yield [
            [
                'read_timeout' => 20.,
                'write_timeout' => 30.,
                'connection_timeout' => 40.,
                'qos_prefetch_count' => 10,
                'dsn' => 'amqp://user:pass@host:10000/vhost?qos_prefetch_count=20',
            ],
            [
                'host' => 'host',
                'port' => 10000,
                'vhost' => 'vhost',
                'user' => 'user',
                'pass' => 'pass',
                'read_timeout' => 20.,
                'write_timeout' => 30.,
                'connection_timeout' => 40.,
                'persisted' => true,
                'lazy' => true,
                'qos_prefetch_count' => 20,
                'qos_prefetch_size' => 0,
                'qos_global' => false,
                'heartbeat' => 0.0,
            ],
        ];
    }
}
