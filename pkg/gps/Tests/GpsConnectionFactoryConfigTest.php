<?php

namespace Enqueue\Gps\Tests;

use Enqueue\Gps\GpsConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use PHPUnit\Framework\TestCase;

/**
 * The class contains the factory tests dedicated to configuration.
 */
class GpsConnectionFactoryConfigTest extends TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string, null or instance of Google\Cloud\PubSub\PubSubClient');

        new GpsConnectionFactory(new \stdClass());
    }

    public function testThrowIfSchemeIsNotAmqp()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given scheme protocol "http" is not supported. It must be "gps"');

        new GpsConnectionFactory('http://example.com');
    }

    public function testThrowIfDsnCouldNotBeParsed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The DSN is invalid.');

        new GpsConnectionFactory('foo');
    }

    /**
     * @dataProvider provideConfigs
     */
    public function testShouldParseConfigurationAsExpected($config, $expectedConfig)
    {
        $factory = new GpsConnectionFactory($config);

        $this->assertAttributeEquals($expectedConfig, 'config', $factory);
    }

    public static function provideConfigs()
    {
        yield [
            null,
            [
                'lazy' => true,
            ],
        ];

        yield [
            'gps:',
            [
                'lazy' => true,
            ],
        ];

        yield [
            [],
            [
                'lazy' => true,
            ],
        ];

        yield [
            'gps:?foo=fooVal&projectId=mqdev&emulatorHost=http%3A%2F%2Fgoogle-pubsub%3A8085',
            [
                'foo' => 'fooVal',
                'projectId' => 'mqdev',
                'emulatorHost' => 'http://google-pubsub:8085',
                'hasEmulator' => true,
                'lazy' => true,
            ],
        ];

        yield [
            ['dsn' => 'gps:?foo=fooVal&projectId=mqdev&emulatorHost=http%3A%2F%2Fgoogle-pubsub%3A8085'],
            [
                'foo' => 'fooVal',
                'projectId' => 'mqdev',
                'emulatorHost' => 'http://google-pubsub:8085',
                'hasEmulator' => true,
                'lazy' => true,
            ],
        ];

        yield [
            ['foo' => 'fooVal', 'projectId' => 'mqdev', 'emulatorHost' => 'http://Fgoogle-pubsub:8085', 'lazy' => false],
            [
                'foo' => 'fooVal',
                'projectId' => 'mqdev',
                'emulatorHost' => 'http://Fgoogle-pubsub:8085',
                'lazy' => false,
            ],
        ];
    }
}
