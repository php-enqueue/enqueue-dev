<?php

namespace Enqueue\Stomp\Tests;

use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

/**
 * The class contains the factory tests dedicated to configuration.
 */
class StompConnectionFactoryConfigTest extends TestCase
{
    use ClassExtensionTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string or null');

        new StompConnectionFactory(new \stdClass());
    }

    public function testThrowIfSchemeIsNotAmqp()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN "http://example.com" is not supported. Must start with "stomp:".');

        new StompConnectionFactory('http://example.com');
    }

    public function testThrowIfDsnCouldNotBeParsed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Failed to parse DSN "stomp://:@/"');

        new StompConnectionFactory('stomp://:@/');
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
                'host' => 'localhost',
                'port' => 61613,
                'login' => 'guest',
                'password' => 'guest',
                'vhost' => '/',
                'buffer_size' => 1000,
                'connection_timeout' => 1,
                'sync' => false,
                'lazy' => true,
                'ssl_on' => false
            ],
        ];

        yield [
            'stomp:',
            [
                'host' => 'localhost',
                'port' => 61613,
                'login' => 'guest',
                'password' => 'guest',
                'vhost' => '/',
                'buffer_size' => 1000,
                'connection_timeout' => 1,
                'sync' => false,
                'lazy' => true,
                'ssl_on' => false
            ],
        ];

        yield [
            [],
            [
                'host' => 'localhost',
                'port' => 61613,
                'login' => 'guest',
                'password' => 'guest',
                'vhost' => '/',
                'buffer_size' => 1000,
                'connection_timeout' => 1,
                'sync' => false,
                'lazy' => true,
                'ssl_on' => false
            ],
        ];

        yield [
            'stomp://localhost:1234?foo=bar&lazy=0&sync=true',
            [
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
                'ssl_on' => false
            ],
        ];

        yield [
            ['host' => 'localhost', 'port' => 1234, 'foo' => 'bar'],
            [
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
                'ssl_on' => false
            ],
        ];
    }
}
