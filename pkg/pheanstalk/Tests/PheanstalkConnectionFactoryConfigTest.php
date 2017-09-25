<?php

namespace Enqueue\Pheanstalk\Tests;

use Enqueue\Pheanstalk\PheanstalkConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

/**
 * The class contains the factory tests dedicated to configuration.
 */
class PheanstalkConnectionFactoryConfigTest extends TestCase
{
    use ClassExtensionTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string or null');

        new PheanstalkConnectionFactory(new \stdClass());
    }

    public function testThrowIfSchemeIsNotBeanstalkAmqp()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN scheme "http" is not supported. Could be "beanstalk" only.');

        new PheanstalkConnectionFactory('http://example.com');
    }

    public function testThrowIfDsnCouldNotBeParsed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Failed to parse DSN "beanstalk://:@/"');

        new PheanstalkConnectionFactory('beanstalk://:@/');
    }

    /**
     * @dataProvider provideConfigs
     *
     * @param mixed $config
     * @param mixed $expectedConfig
     */
    public function testShouldParseConfigurationAsExpected($config, $expectedConfig)
    {
        $factory = new PheanstalkConnectionFactory($config);

        $this->assertAttributeEquals($expectedConfig, 'config', $factory);
    }

    public static function provideConfigs()
    {
        yield [
            null,
            [
                'host' => 'localhost',
                'port' => 11300,
                'timeout' => null,
                'persisted' => true,
            ],
        ];

        yield [
            'beanstalk:',
            [
                'host' => 'localhost',
                'port' => 11300,
                'timeout' => null,
                'persisted' => true,
            ],
        ];

        yield [
            [],
            [
                'host' => 'localhost',
                'port' => 11300,
                'timeout' => null,
                'persisted' => true,
            ],
        ];

        yield [
            'beanstalk://theHost:1234',
            [
                'host' => 'theHost',
                'port' => 1234,
                'timeout' => null,
                'persisted' => true,
            ],
        ];

        yield [
            ['host' => 'theHost', 'port' => 1234],
            [
                'host' => 'theHost',
                'port' => 1234,
                'timeout' => null,
                'persisted' => true,
            ],
        ];

        yield [
            ['host' => 'theHost'],
            [
                'host' => 'theHost',
                'port' => 11300,
                'timeout' => null,
                'persisted' => true,
            ],
        ];

        yield [
            ['host' => 'theHost', 'timeout' => 123],
            [
                'host' => 'theHost',
                'port' => 11300,
                'timeout' => 123,
                'persisted' => true,
            ],
        ];

        yield [
            'beanstalk://theHost:1234?timeout=123&persisted=1',
            [
                'host' => 'theHost',
                'port' => 1234,
                'timeout' => 123,
                'persisted' => 1,
            ],
        ];
    }
}
