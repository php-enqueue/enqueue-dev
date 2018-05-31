<?php

namespace Enqueue\Dbal\Tests;

use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Test\ClassExtensionTrait;
use PHPUnit\Framework\TestCase;

/**
 * The class contains the factory tests dedicated to configuration.
 */
class DbalConnectionFactoryConfigTest extends TestCase
{
    use ClassExtensionTrait;

    public function testThrowNeitherArrayStringNorNullGivenAsConfig()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The config must be either an array of options, a DSN string or null');

        new DbalConnectionFactory(new \stdClass());
    }

    public function testThrowIfSchemeIsNotSupported()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The given DSN schema "http" is not supported. There are supported schemes: "db2", "ibm_db2", "mssql", "pdo_sqlsrv", "mysql", "mysql2", "pdo_mysql", "pgsql", "postgres", "postgresql", "pdo_pgsql", "sqlite", "sqlite3", "pdo_sqlite"');

        new DbalConnectionFactory('http://example.com');
    }

    public function testThrowIfDsnCouldNotBeParsed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Schema is empty');

        new DbalConnectionFactory('invalidDSN');
    }

    /**
     * @dataProvider provideConfigs
     *
     * @param mixed $config
     * @param mixed $expectedConfig
     */
    public function testShouldParseConfigurationAsExpected($config, $expectedConfig)
    {
        $factory = new DbalConnectionFactory($config);

        $this->assertAttributeEquals($expectedConfig, 'config', $factory);
    }

    public static function provideConfigs()
    {
        yield [
            null,
            [
                'lazy' => true,
                'connection' => [
                    'url' => 'mysql://root@localhost',
                ],
            ],
        ];

        yield [
            'mysql:',
            [
                'lazy' => true,
                'connection' => [
                    'url' => 'mysql://root@localhost',
                ],
            ],
        ];

        yield [
            'pgsql:',
            [
                'lazy' => true,
                'connection' => [
                    'url' => 'pgsql://root@localhost',
                ],
            ],
        ];

        yield [
            'mysql://user:pass@host:10000/db',
            [
                'lazy' => true,
                'connection' => [
                    'url' => 'mysql://user:pass@host:10000/db',
                ],
            ],
        ];

        yield [
            'pdo_mysql://user:pass@host:10001/db',
            [
                'lazy' => true,
                'connection' => [
                    'url' => 'pdo_mysql://user:pass@host:10001/db',
                ],
            ],
        ];

        yield [
            [],
            [
                'lazy' => true,
                'connection' => [
                    'url' => 'mysql://root@localhost',
                ],
            ],
        ];

        yield [
            ['table_name' => 'a_queue_table', 'connection' => ['foo' => 'fooVal', 'bar' => 'barVal']],
            ['table_name' => 'a_queue_table', 'connection' => ['foo' => 'fooVal', 'bar' => 'barVal']],
        ];
    }
}
