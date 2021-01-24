<?php

namespace Enqueue\RdKafka\Tests;

use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use Enqueue\Test\ReadAttributeTrait;
use PHPUnit\Framework\TestCase;

/**
 * The class contains the factory tests dedicated to configuration.
 */
class RdKafkaConnectionFactoryConfigTest extends TestCase
{
    use ClassExtensionTrait;
    use ReadAttributeTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string or null');

        new RdKafkaConnectionFactory(new \stdClass());
    }

    public function testThrowIfSchemeIsNotSupported()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN scheme "http" is not supported. Could be "kafka" only.');

        new RdKafkaConnectionFactory('http://example.com');
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

        $config = $this->readAttribute($factory, 'config');

        $this->assertNotEmpty($config['global']['group.id']);

        $config['global']['group.id'] = 'group-id';
        $this->assertSame($expectedConfig, $config);
    }

    public static function provideConfigs()
    {
        yield [
            null,
            [
                'global' => [
                    'group.id' => 'group-id',
                    'metadata.broker.list' => 'localhost:9092',
                ],
            ],
        ];

        yield [
            'kafka:',
            [
                'global' => [
                    'group.id' => 'group-id',
                    'metadata.broker.list' => 'localhost:9092',
                ],
            ],
        ];

        yield [
            'kafka://user:pass@host:10000/db',
            [
                'global' => [
                    'group.id' => 'group-id',
                    'metadata.broker.list' => 'host:10000',
                ],
            ],
        ];

        yield [
            [],
            [
                'global' => [
                    'group.id' => 'group-id',
                    'metadata.broker.list' => 'localhost:9092',
                ],
            ],
        ];
    }
}
