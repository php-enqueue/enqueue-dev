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
        $this->expectExceptionMessage('The given DSN "http://example.com" is not supported. Must start with "file:');

        new FsConnectionFactory('http://example.com');
    }

    public function testThrowIfDsnCouldNotBeParsed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Failed to parse DSN path ":@/". The path must start with "/"');

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
                'polling_interval' => 100,
            ],
        ];

        yield [
            '',
            [
                'path' => sys_get_temp_dir().'/enqueue',
                'pre_fetch_count' => 1,
                'chmod' => 0600,
                'polling_interval' => 100,
            ],
        ];

        yield [
            [],
            [
                'path' => sys_get_temp_dir().'/enqueue',
                'pre_fetch_count' => 1,
                'chmod' => 0600,
                'polling_interval' => 100,
            ],
        ];

        yield [
            'file:',
            [
                'path' => sys_get_temp_dir().'/enqueue',
                'pre_fetch_count' => 1,
                'chmod' => 0600,
                'polling_interval' => 100,
            ],
        ];

        yield [
            '/foo/bar/baz',
            [
                'path' => '/foo/bar/baz',
                'pre_fetch_count' => 1,
                'chmod' => 0600,
                'polling_interval' => 100,
            ],
        ];

        yield [
            'file:///foo/bar/baz',
            [
                'path' => '/foo/bar/baz',
                'pre_fetch_count' => 1,
                'chmod' => 0600,
                'polling_interval' => 100,
            ],
        ];

        yield [
            'file:///foo/bar/baz?pre_fetch_count=100&chmod=0666',
            [
                'path' => '/foo/bar/baz',
                'pre_fetch_count' => 100,
                'chmod' => 0666,
                'polling_interval' => 100,
            ],
        ];
    }
}
