<?php

namespace Enqueue\RdKafka\Tests;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\Test\ReadAttributeTrait;
use PHPUnit\Framework\TestCase;

/**
 * @group rdkafka
 */
class RdKafkaConnectionFactoryTest extends TestCase
{
    use ReadAttributeTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string or null');

        new RdKafkaConnectionFactory(new \stdClass());
    }

    public function testThrowIfSchemeIsNotBeanstalkAmqp()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN scheme "http" is not supported. Could be "kafka" only.');

        new RdKafkaConnectionFactory('http://example.com');
    }

    public function testThrowIfDsnCouldNotBeParsed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Failed to parse DSN "kafka://:@/"');

        new RdKafkaConnectionFactory('kafka://:@/');
    }

    public function testShouldBeExpectedDefaultConfig()
    {
        $factory = new RdKafkaConnectionFactory(null);

        $config = $this->readAttribute($factory, 'config');

        $this->assertNotEmpty($config['global']['group.id']);

        $config['global']['group.id'] = 'group-id';
        $this->assertSame([
            'global' => [
                'group.id' => 'group-id',
                'metadata.broker.list' => 'localhost:9092',
            ],
        ], $config);
    }

    public function testShouldBeExpectedDefaultDsnConfig()
    {
        $factory = new RdKafkaConnectionFactory('kafka:');

        $config = $this->readAttribute($factory, 'config');

        $this->assertNotEmpty($config['global']['group.id']);

        $config['global']['group.id'] = 'group-id';
        $this->assertSame([
            'global' => [
                'group.id' => 'group-id',
                'metadata.broker.list' => 'localhost:9092',
            ],
        ], $config);
    }

    /**
     * @dataProvider provideConfigs
     *
     * @param mixed $config
     * @param mixed $expectedConfig
     */
    public function testShouldParseConfigurationAsExpected($config, $expectedConfig)
    {
        $factory = new RdKafkaConnectionFactory($config);

        $this->assertAttributeEquals($expectedConfig, 'config', $factory);
    }

    public static function provideConfigs()
    {
        yield [
            'kafka://theHost:1234?global%5Bgroup.id%5D=group-id',
            [
                'global' => [
                    'metadata.broker.list' => 'theHost:1234',
                    'group.id' => 'group-id',
                ],
            ],
        ];

        yield [
            [
                'global' => [
                    'metadata.broker.list' => 'theHost:1234',
                    'group.id' => 'group-id',
                ],
            ],
            [
                'global' => [
                    'metadata.broker.list' => 'theHost:1234',
                    'group.id' => 'group-id',
                ],
            ],
        ];

        yield [
            [
                'global' => [
                    'group.id' => 'group-id',
                ],
            ],
            [
                'global' => [
                    'metadata.broker.list' => 'localhost:9092',
                    'group.id' => 'group-id',
                ],
            ],
        ];
    }
}
