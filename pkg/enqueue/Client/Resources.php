<?php

namespace Enqueue\Client;

use Enqueue\Client\Driver\AmqpDriver;
use Enqueue\Client\Driver\DbalDriver;
use Enqueue\Client\Driver\FsDriver;
use Enqueue\Client\Driver\GpsDriver;
use Enqueue\Client\Driver\MongodbDriver;
use Enqueue\Client\Driver\NullDriver;
use Enqueue\Client\Driver\RabbitMqDriver;
use Enqueue\Client\Driver\RabbitMqStompDriver;
use Enqueue\Client\Driver\RdKafkaDriver;
use Enqueue\Client\Driver\RedisDriver;
use Enqueue\Client\Driver\SqsDriver;
use Enqueue\Client\Driver\StompDriver;

final class Resources
{
    /**
     * [client driver class => [
     *   schemes => [schemes strings],
     *   package => package name,
     * ].
     *
     * @var array
     */
    private static $knownDrivers = null;

    private function __construct()
    {
    }

    public static function getAvailableDrivers(): array
    {
        $map = self::getKnownDrivers();

        $availableMap = [];
        foreach ($map as $driverClass => $item) {
            if (class_exists($driverClass)) {
                $availableMap[$driverClass] = $item;
            }
        }

        return $availableMap;
    }

    public static function getKnownDrivers(): array
    {
        if (null === self::$knownDrivers) {
            $map = [];

            $map[AmqpDriver::class] = [
                'schemes' => ['amqp', 'amqps'],
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/amqp-bunny'],
            ];
            $map[RabbitMqDriver::class] = [
                'schemes' => ['amqp', 'amqps'],
                'requiredSchemeExtensions' => ['rabbitmq'],
                'packages' => ['enqueue/enqueue', 'enqueue/amqp-bunny'],
            ];
            $map[FsDriver::class] = [
                'schemes' => ['file'],
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/fs'],
            ];
            $map[NullDriver::class] = [
                'schemes' => ['null'],
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/null'],
            ];
            $map[GpsDriver::class] = [
                'schemes' => ['gps'],
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/gps'],
            ];
            $map[RedisDriver::class] = [
                'schemes' => ['redis'],
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/redis'],
            ];
            $map[SqsDriver::class] = [
                'schemes' => ['sqs'],
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/sqs'],
            ];
            $map[StompDriver::class] = [
                'schemes' => ['stomp'],
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/stomp'],
            ];
            $map[RabbitMqStompDriver::class] = [
                'schemes' => ['stomp'],
                'requiredSchemeExtensions' => ['rabbitmq'],
                'packages' => ['enqueue/enqueue', 'enqueue/stomp'],
            ];
            $map[RdKafkaDriver::class] = [
                'schemes' => ['kafka', 'rdkafka'],
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/rdkafka'],
            ];
            $map[MongodbDriver::class] = [
                'schemes' => ['mongodb'],
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/mongodb'],
            ];
            $map[DbalDriver::class] = [
                'schemes' => [
                    'db2',
                    'ibm-db2',
                    'mssql',
                    'sqlsrv',
                    'mysql',
                    'mysql2',
                    'mysql',
                    'pgsql',
                    'postgres',
                    'pgsql',
                    'sqlite',
                    'sqlite3',
                    'sqlite',
                ],
                'requiredSchemeExtensions' => ['pdo'],
                'package' => ['enqueue/enqueue', 'enqueue/dbal'],
            ];

            self::$knownDrivers = $map;
        }

        return self::$knownDrivers;
    }

    public static function addDriver(string $driverClass, array $schemes, array $requiredExtensions, array $packages): void
    {
        if (class_exists($driverClass)) {
            if (false == (new \ReflectionClass($driverClass))->implementsInterface(DriverInterface::class)) {
                throw new \InvalidArgumentException(sprintf('The driver class "%s" must implement "%s" interface.', $driverClass, DriverInterface::class));
            }
        }

        if (empty($schemes)) {
            throw new \InvalidArgumentException('Schemes could not be empty.');
        }
        if (empty($packages)) {
            throw new \InvalidArgumentException('Packages could not be empty.');
        }

        self::getKnownDrivers();
        self::$knownDrivers[$driverClass] = [
            'schemes' => $schemes,
            'requiredSchemeExtensions' => $requiredExtensions,
            'packages' => $packages,
        ];
    }
}
