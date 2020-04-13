<?php

namespace Enqueue\Sns\Tests;

use Enqueue\Sns\SnsConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

/**
 * The class contains the factory tests dedicated to configuration.
 */
class SnsConnectionFactoryConfigTest extends TestCase
{
    use ClassExtensionTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string, null or instance of AsyncAws\Sns\SnsClient');

        new SnsConnectionFactory(new \stdClass());
    }

    public function testThrowIfSchemeIsNotAmqp()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given scheme protocol "http" is not supported. It must be "sns"');

        new SnsConnectionFactory('http://example.com');
    }

    public function testThrowIfDsnCouldNotBeParsed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The DSN is invalid.');

        new SnsConnectionFactory('foo');
    }

    /**
     * @dataProvider provideConfigs
     *
     * @param mixed $config
     * @param mixed $expectedConfig
     */
    public function testShouldParseConfigurationAsExpected($config, $expectedConfig)
    {
        $factory = new SnsConnectionFactory($config);

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
                'endpoint' => null,
                'profile' => null,
            ],
        ];

        yield [
            'sns:',
            [
                'key' => null,
                'secret' => null,
                'token' => null,
                'region' => null,
                'endpoint' => null,
                'profile' => null,
            ],
        ];

        yield [
            [],
            [
                'key' => null,
                'secret' => null,
                'token' => null,
                'region' => null,
                'endpoint' => null,
                'profile' => null,
            ],
        ];

        yield [
            'sns:?key=theKey&secret=theSecret&token=theToken',
            [
                'key' => 'theKey',
                'secret' => 'theSecret',
                'token' => 'theToken',
                'region' => null,
                'endpoint' => null,
                'profile' => null,
            ],
        ];

        yield [
            ['dsn' => 'sns:?key=theKey&secret=theSecret&token=theToken'],
            [
                'key' => 'theKey',
                'secret' => 'theSecret',
                'token' => 'theToken',
                'region' => null,
                'endpoint' => null,
                'profile' => null,
            ],
        ];

        yield [
            ['key' => 'theKey', 'secret' => 'theSecret', 'token' => 'theToken'],
            [
                'key' => 'theKey',
                'secret' => 'theSecret',
                'token' => 'theToken',
                'region' => null,
                'endpoint' => null,
                'profile' => null,
            ],
        ];

        yield [
            [
                'key' => 'theKey',
                'secret' => 'theSecret',
                'token' => 'theToken',
                'endpoint' => 'http://localstack:1111',
                'profile' => 'theProfile',
            ],
            [
                'key' => 'theKey',
                'secret' => 'theSecret',
                'token' => 'theToken',
                'region' => null,
                'endpoint' => 'http://localstack:1111',
                'profile' => 'theProfile',
            ],
        ];
    }
}
