<?php

namespace Enqueue\Client;

use Enqueue\Client\Amqp\AmqpDriver;
use Enqueue\Client\Amqp\RabbitMqDriver;
use Enqueue\Dbal\Client\DbalDriver;
use Enqueue\Fs\Client\FsDriver;
use Enqueue\Gps\Client\GpsDriver;
use Enqueue\Mongodb\Client\MongodbDriver;
use Enqueue\Null\Client\NullDriver;
use Enqueue\RdKafka\Client\RdKafkaDriver;
use Enqueue\Redis\Client\RedisDriver;
use Enqueue\Sqs\Client\SqsDriver;
use Enqueue\Stomp\Client\RabbitMqStompDriver;
use Enqueue\Stomp\Client\StompDriver;

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
                'schemes' => ['amqp'],
                'requiredSchemeExtensions' => [],
                'package' => 'enqueue/enqueue',
            ];
            $map[RabbitMqDriver::class] = [
                'schemes' => ['amqp'],
                'requiredSchemeExtensions' => ['rabbitmq'],
                'package' => 'enqueue/enqueue',
            ];
            $map[FsDriver::class] = [
                'schemes' => ['file'],
                'requiredSchemeExtensions' => [],
                'package' => 'enqueue/fs',
            ];
            $map[NullDriver::class] = [
                'schemes' => ['null'],
                'requiredSchemeExtensions' => [],
                'package' => 'enqueue/null',
            ];
            $map[GpsDriver::class] = [
                'schemes' => ['gps'],
                'requiredSchemeExtensions' => [],
                'package' => 'enqueue/gps',
            ];
            $map[RedisDriver::class] = [
                'schemes' => ['redis'],
                'requiredSchemeExtensions' => [],
                'package' => 'enqueue/redis',
            ];
            $map[SqsDriver::class] = [
                'schemes' => ['sqs'],
                'requiredSchemeExtensions' => [],
                'package' => 'enqueue/sqs',
            ];
            $map[StompDriver::class] = [
                'schemes' => ['stomp'],
                'requiredSchemeExtensions' => [],
                'package' => 'enqueue/stomp',
            ];
            $map[RabbitMqStompDriver::class] = [
                'schemes' => ['stomp'],
                'requiredSchemeExtensions' => ['rabbitmq'],
                'package' => 'enqueue/stomp',
            ];
            $map[RdKafkaDriver::class] = [
                'schemes' => ['kafka', 'rdkafka'],
                'requiredSchemeExtensions' => [],
                'package' => 'enqueue/rdkafka',
            ];
            $map[MongodbDriver::class] = [
                'schemes' => ['mongodb'],
                'requiredSchemeExtensions' => [],
                'package' => 'enqueue/mongodb',
            ];
            $map[DbalDriver::class] = [
                'schemes' => [
                    'db2',
                    'ibm_db2',
                    'mssql',
                    'pdo_sqlsrv',
                    'mysql',
                    'mysql2',
                    'pdo_mysql',
                    'pgsql',
                    'postgres',
                    'pdo_pgsql',
                    'sqlite',
                    'sqlite3',
                    'pdo_sqlite',
                ],
                'requiredSchemeExtensions' => [],
                'package' => 'enqueue/dbal',
            ];

            self::$knownDrivers = $map;
        }

        return self::$knownDrivers;
    }

    public static function addDriver(string $driverClass, array $schemes, array $requiredExtensions, string $package): void
    {
        if (class_exists($driverClass)) {
            if (false == (new \ReflectionClass($driverClass))->implementsInterface(DriverInterface::class)) {
                throw new \InvalidArgumentException(sprintf('The driver class "%s" must implement "%s" interface.', $driverClass, DriverInterface::class));
            }
        }

        if (empty($schemes)) {
            throw new \InvalidArgumentException('Schemes could not be empty.');
        }
        if (empty($package)) {
            throw new \InvalidArgumentException('Package name could not be empty.');
        }

        self::getKnownDrivers();
        self::$knownDrivers[$driverClass] = [
            'schemes' => $schemes,
            'requiredSchemeExtensions' => $requiredExtensions,
            'package' => $package,
        ];
    }
}
