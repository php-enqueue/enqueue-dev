<?php

namespace Enqueue\Sqs\Tests;

use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

/**
 * The class contains the factory tests dedicated to configuration.
 */
class SqsConnectionFactoryConfigTest extends TestCase
{
    use ClassExtensionTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string, or null');

        new SqsConnectionFactory(new \stdClass());
    }

    public function testThrowIfSchemeIsNotAmqp()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN "http://example.com" is not supported. Must start with "sqs:".');

        new SqsConnectionFactory('http://example.com');
    }

    public function testThrowIfDsnCouldNotBeParsed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Failed to parse DSN "sqs://:@/"');

        new SqsConnectionFactory('sqs://:@/');
    }

    /**
     * @dataProvider provideConfigs
     *
     * @param mixed $config
     * @param mixed $expectedConfig
     */
    public function testShouldParseConfigurationAsExpected($config, $expectedConfig)
    {
        $factory = new SqsConnectionFactory($config);

        $this->assertAttributeEquals($expectedConfig, 'config', $factory);
    }

    public static function provideConfigs()
    {
        yield [
            null,
            [
                'key' => null,
                'secret' => null,
                'token' => null,
                'region' => null,
                'retries' => 3,
                'version' => '2012-11-05',
                'lazy' => true,
            ],
        ];

        yield [
            'sqs:',
            [
                'key' => null,
                'secret' => null,
                'token' => null,
                'region' => null,
                'retries' => 3,
                'version' => '2012-11-05',
                'lazy' => true,
            ],
        ];

        yield [
            [],
            [
                'key' => null,
                'secret' => null,
                'token' => null,
                'region' => null,
                'retries' => 3,
                'version' => '2012-11-05',
                'lazy' => true,
            ],
        ];

        yield [
            'sqs:?key=theKey&secret=theSecret&token=theToken&lazy=0',
            [
                'key' => 'theKey',
                'secret' => 'theSecret',
                'token' => 'theToken',
                'region' => null,
                'retries' => 3,
                'version' => '2012-11-05',
                'lazy' => false,
            ],
        ];

        yield [
            ['dsn' => 'sqs:?key=theKey&secret=theSecret&token=theToken&lazy=0'],
            [
                'key' => 'theKey',
                'secret' => 'theSecret',
                'token' => 'theToken',
                'region' => null,
                'retries' => 3,
                'version' => '2012-11-05',
                'lazy' => false,
            ],
        ];

        yield [
            ['key' => 'theKey', 'secret' => 'theSecret', 'token' => 'theToken', 'lazy' => false],
            [
                'key' => 'theKey',
                'secret' => 'theSecret',
                'token' => 'theToken',
                'region' => null,
                'retries' => 3,
                'version' => '2012-11-05',
                'lazy' => false,
            ],
        ];
    }
}
