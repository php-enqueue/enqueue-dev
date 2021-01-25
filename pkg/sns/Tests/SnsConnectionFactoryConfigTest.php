<?php

namespace Enqueue\Sns\Tests;

use Enqueue\Sns\SnsConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use PHPUnit\Framework\TestCase;

/**
 * The class contains the factory tests dedicated to configuration.
 */
class SnsConnectionFactoryConfigTest extends TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string, null or instance of Aws\Sns\SnsClient');

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
                'version' => '2010-03-31',
                'lazy' => true,
                'endpoint' => null,
            ],
        ];

        yield [
            'sns:',
            [
                'key' => null,
                'secret' => null,
                'token' => null,
                'region' => null,
                'version' => '2010-03-31',
                'lazy' => true,
                'endpoint' => null,
            ],
        ];

        yield [
            [],
            [
                'key' => null,
                'secret' => null,
                'token' => null,
                'region' => null,
                'version' => '2010-03-31',
                'lazy' => true,
                'endpoint' => null,
            ],
        ];

        yield [
            'sns:?key=theKey&secret=theSecret&token=theToken&lazy=0',
            [
                'key' => 'theKey',
                'secret' => 'theSecret',
                'token' => 'theToken',
                'region' => null,
                'version' => '2010-03-31',
                'lazy' => false,
                'endpoint' => null,
            ],
        ];

        yield [
            ['dsn' => 'sns:?key=theKey&secret=theSecret&token=theToken&lazy=0'],
            [
                'key' => 'theKey',
                'secret' => 'theSecret',
                'token' => 'theToken',
                'region' => null,
                'version' => '2010-03-31',
                'lazy' => false,
                'endpoint' => null,
            ],
        ];

        yield [
            ['key' => 'theKey', 'secret' => 'theSecret', 'token' => 'theToken', 'lazy' => false],
            [
                'key' => 'theKey',
                'secret' => 'theSecret',
                'token' => 'theToken',
                'region' => null,
                'version' => '2010-03-31',
                'lazy' => false,
                'endpoint' => null,
            ],
        ];

        yield [
            [
                'key' => 'theKey',
                'secret' => 'theSecret',
                'token' => 'theToken',
                'lazy' => false,
                'endpoint' => 'http://localstack:1111',
            ],
            [
                'key' => 'theKey',
                'secret' => 'theSecret',
                'token' => 'theToken',
                'region' => null,
                'version' => '2010-03-31',
                'lazy' => false,
                'endpoint' => 'http://localstack:1111',
            ],
        ];
    }
}
