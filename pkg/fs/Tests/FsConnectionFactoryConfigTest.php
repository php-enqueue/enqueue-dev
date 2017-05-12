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
    }
}
