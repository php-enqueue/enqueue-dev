<?php

namespace Enqueue\Client;

use Enqueue\Client\Driver\AmqpDriver;
use Enqueue\Client\Driver\DbalDriver;
use Enqueue\Client\Driver\FsDriver;
use Enqueue\Client\Driver\GenericDriver;
use Enqueue\Client\Driver\GpsDriver;
use Enqueue\Client\Driver\MongodbDriver;
use Enqueue\Client\Driver\RabbitMqDriver;
use Enqueue\Client\Driver\RabbitMqStompDriver;
use Enqueue\Client\Driver\RdKafkaDriver;
use Enqueue\Client\Driver\RedisDriver;
use Enqueue\Client\Driver\SnsQsDriver;
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
        foreach ($map as $item) {
            if (class_exists($item['driverClass'])) {
                $availableMap[] = $item;
            }
        }

        return $availableMap;
    }

    public static function getKnownDrivers(): array
    {
        if (null === self::$knownDrivers) {
            $map = [];

            $map[] = [
                'schemes' => ['amqp', 'amqps'],
                'driverClass' => AmqpDriver::class,
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/amqp-bunny'],
            ];
            $map[] = [
                'schemes' => ['amqp', 'amqps'],
                'driverClass' => RabbitMqDriver::class,
                'requiredSchemeExtensions' => ['rabbitmq'],
                'packages' => ['enqueue/enqueue', 'enqueue/amqp-bunny'],
            ];
            $map[] = [
                'schemes' => ['file'],
                'driverClass' => FsDriver::class,
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/fs'],
            ];
            $map[] = [
                'schemes' => ['null'],
                'driverClass' => GenericDriver::class,
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/null'],
            ];
            $map[] = [
                'schemes' => ['gps'],
                'driverClass' => GpsDriver::class,
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/gps'],
            ];
            $map[] = [
                'schemes' => ['redis', 'rediss'],
                'driverClass' => RedisDriver::class,
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/redis'],
            ];
            $map[] = [
                'schemes' => ['sqs'],
                'driverClass' => SqsDriver::class,
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/sqs'],
            ];
            $map[] = [
                'schemes' => ['sns'],
                'driverClass' => GenericDriver::class,
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/sns'],
            ];
            $map[] = [
                'schemes' => ['snsqs'],
                'driverClass' => SnsQsDriver::class,
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/sqs', 'enqueue/sns', 'enqueue/snsqs'],
            ];
            $map[] = [
                'schemes' => ['stomp'],
                'driverClass' => StompDriver::class,
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/stomp'],
            ];
            $map[] = [
                'schemes' => ['stomp'],
                'driverClass' => RabbitMqStompDriver::class,
                'requiredSchemeExtensions' => ['rabbitmq'],
                'packages' => ['enqueue/enqueue', 'enqueue/stomp'],
            ];
            $map[] = [
                'schemes' => ['kafka', 'rdkafka'],
                'driverClass' => RdKafkaDriver::class,
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/rdkafka'],
            ];
            $map[] = [
                'schemes' => ['mongodb'],
                'driverClass' => MongodbDriver::class,
                'requiredSchemeExtensions' => [],
                'packages' => ['enqueue/enqueue', 'enqueue/mongodb'],
            ];
            $map[] = [
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
                'driverClass' => DbalDriver::class,
                'requiredSchemeExtensions' => [],
                'package' => ['enqueue/enqueue', 'enqueue/dbal'],
            ];
            $map[] = [
                'schemes' => ['gearman'],
                'driverClass' => GenericDriver::class,
                'requiredSchemeExtensions' => [],
                'package' => ['enqueue/enqueue', 'enqueue/gearman'],
            ];
            $map[] = [
                'schemes' => ['beanstalk'],
                'driverClass' => GenericDriver::class,
                'requiredSchemeExtensions' => [],
                'package' => ['enqueue/enqueue', 'enqueue/pheanstalk'],
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
        self::$knownDrivers[] = [
            'schemes' => $schemes,
            'driverClass' => $driverClass,
            'requiredSchemeExtensions' => $requiredExtensions,
            'packages' => $packages,
        ];
    }
}
