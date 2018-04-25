<?php

namespace Enqueue\Gearman\Tests;

use Enqueue\Gearman\GearmanConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

/**
 * The class contains the factory tests dedicated to configuration.
 */
class GearmanConnectionFactoryConfigTest extends TestCase
{
    use ClassExtensionTrait;
    use SkipIfGearmanExtensionIsNotInstalledTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string or null');

        new GearmanConnectionFactory(new \stdClass());
    }

    public function testThrowIfSchemeIsNotGearmanAmqp()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN scheme "http" is not supported. Could be "gearman" only.');

        new GearmanConnectionFactory('http://example.com');
    }

    public function testThrowIfDsnCouldNotBeParsed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Failed to parse DSN "gearman://:@/"');

        new GearmanConnectionFactory('gearman://:@/');
    }

    /**
     * @dataProvider provideConfigs
     *
     * @param mixed $config
     * @param mixed $expectedConfig
     */
    public function testShouldParseConfigurationAsExpected($config, $expectedConfig)
    {
        $factory = new GearmanConnectionFactory($config);

        $this->assertAttributeEquals($expectedConfig, 'config', $factory);
    }

    public static function provideConfigs()
    {
        yield [
            null,
            [
                'host' => 'localhost',
                'port' => 4730,
            ],
        ];

        yield [
            'gearman:',
            [
                'host' => 'localhost',
                'port' => 4730,
            ],
        ];

        yield [
            [],
            [
                'host' => 'localhost',
                'port' => 4730,
            ],
        ];

        yield [
            'gearman://theHost:1234',
            [
                'host' => 'theHost',
                'port' => 1234,
            ],
        ];

        yield [
            ['host' => 'theHost', 'port' => 1234],
            [
                'host' => 'theHost',
                'port' => 1234,
            ],
        ];

        yield [
            ['host' => 'theHost'],
            [
                'host' => 'theHost',
                'port' => 4730,
            ],
        ];
    }
}
