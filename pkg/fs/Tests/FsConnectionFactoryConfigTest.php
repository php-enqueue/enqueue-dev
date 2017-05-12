<?php

namespace Enqueue\Fs\Tests;

use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

/**
 * The class contains the factory tests dedicated to configuration.
 */
class FsConnectionFactoryConfigTest extends TestCase
{
    use ClassExtensionTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string or null');

        new FsConnectionFactory(new \stdClass());
    }

    public function testThrowIfSchemeIsNotAmqp()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN scheme "%s" is not supported. Could be "file" only.');

        new FsConnectionFactory('http://example.com');
    }

    public function testThrowIfDsnCouldNotBeParsed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Failed to parse DSN "file://:@/"');

        new FsConnectionFactory('file://:@/');
    }

    /**
     * @dataProvider provideConfigs
     *
     * @param mixed $config
     * @param mixed $expectedConfig
     */
    public function testShouldParseConfigurationAsExpected($config, $expectedConfig)
    {
        $factory = new FsConnectionFactory($config);

        $this->assertAttributeEquals($expectedConfig, 'config', $factory);
    }

    public static function provideConfigs()
    {
        yield [
            null,
            [
                'path' => sys_get_temp_dir().'/enqueue',
                'pre_fetch_count' => 1,
                'chmod' => 0600,
            ],
        ];

        yield [
            '',
            [
                'path' => sys_get_temp_dir().'/enqueue',
                'pre_fetch_count' => 1,
                'chmod' => 0600,
            ],
        ];

        yield [
            [],
            [
                'path' => sys_get_temp_dir().'/enqueue',
                'pre_fetch_count' => 1,
                'chmod' => 0600,
            ],
        ];

        yield [
            'file://',
            [
                'path' => sys_get_temp_dir().'/enqueue',
                'pre_fetch_count' => 1,
                'chmod' => 0600,
            ],
        ];

        yield [
            __DIR__,
            [
                'path' => __DIR__,
                'pre_fetch_count' => 1,
                'chmod' => 0600,
            ],
        ];

        yield [
            'file:/'.__DIR__,
            [
                'path' => __DIR__,
                'pre_fetch_count' => 1,
                'chmod' => 0600,
            ],
        ];

        yield [
            'file:/'.__DIR__.'?pre_fetch_count=100&chmod=0666',
            [
                'path' => __DIR__,
                'pre_fetch_count' => 100,
                'chmod' => 0666,
            ],
        ];

//        yield [
//            "amqp://user:pass@host:10000/vhost",
//            [
//                'host' => 'host',
//                'port' => 10000,
//                'vhost' => 'vhost',
//                'user' => 'user',
//                'pass' => 'pass',
//                'read_timeout' => null,
//                'write_timeout' => null,
//                'connect_timeout' => null,
//                'persisted' => false,
//                'lazy' => true,
//            ]
//        ];
//
//        yield [
//            "amqp://user%61:%61pass@ho%61st:10000/v%2fhost",
//            [
//                'host' => 'hoast',
//                'port' => 10000,
//                'vhost' => 'v/host',
//                'user' => 'usera',
//                'pass' => 'apass',
//                'read_timeout' => null,
//                'write_timeout' => null,
//                'connect_timeout' => null,
//                'persisted' => false,
//                'lazy' => true,
//            ]
//        ];
//
//        yield [
//            "amqp://",
//            [
//                'host' => 'localhost',
//                'port' => 5672,
//                'vhost' => '/',
//                'user' => 'guest',
//                'pass' => 'guest',
//                'read_timeout' => null,
//                'write_timeout' => null,
//                'connect_timeout' => null,
//                'persisted' => false,
//                'lazy' => true,
//            ]
//        ];
//
//        yield [
//            "amqp://user:pass@host:10000/vhost?connect_timeout=2&lazy=",
//            [
//                'host' => 'host',
//                'port' => 10000,
//                'vhost' => 'vhost',
//                'user' => 'user',
//                'pass' => 'pass',
//                'read_timeout' => null,
//                'write_timeout' => null,
//                'connect_timeout' => '2',
//                'persisted' => false,
//                'lazy' => '',
//            ]
//        ];
//
//        yield [
//            [],
//            [
//                'host' => 'localhost',
//                'port' => 5672,
//                'vhost' => '/',
//                'user' => 'guest',
//                'pass' => 'guest',
//                'read_timeout' => null,
//                'write_timeout' => null,
//                'connect_timeout' => null,
//                'persisted' => false,
//                'lazy' => true,
//            ]
//        ];
//
//        yield [
//            ['lazy' => false, 'host' => 'host'],
//            [
//                'host' => 'host',
//                'port' => 5672,
//                'vhost' => '/',
//                'user' => 'guest',
//                'pass' => 'guest',
//                'read_timeout' => null,
//                'write_timeout' => null,
//                'connect_timeout' => null,
//                'persisted' => false,
//                'lazy' => false,
//            ]
//        ];
    }
}
