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

            $map[AmqpDriver::class] = ['supportedSchemeProtocols' => ['amqp'], 'requiredSchemeExtensions' => []];
            $map[RabbitMqDriver::class] = ['supportedSchemeProtocols' => ['amqp'], 'requiredSchemeExtensions' => ['rabbitmq']];
            $map[FsDriver::class] = ['supportedSchemeProtocols' => ['file'], 'requiredSchemeExtensions' => []];
            $map[NullDriver::class] = ['supportedSchemeProtocols' => ['null'], 'requiredSchemeExtensions' => []];
            $map[GpsDriver::class] = ['supportedSchemeProtocols' => ['gps'], 'requiredSchemeExtensions' => []];
            $map[RedisDriver::class] = ['supportedSchemeProtocols' => ['redis'], 'requiredSchemeExtensions' => []];
            $map[SqsDriver::class] = ['supportedSchemeProtocols' => ['sqs'], 'requiredSchemeExtensions' => []];
            $map[StompDriver::class] = ['supportedSchemeProtocols' => ['stomp'], 'requiredSchemeExtensions' => []];
            $map[RabbitMqStompDriver::class] = ['supportedSchemeProtocols' => ['stomp'], 'requiredSchemeExtensions' => ['rabbitmq']];
            $map[RdKafkaDriver::class] = ['supportedSchemeProtocols' => ['kafka', 'rdkafka'], 'requiredSchemeExtensions' => []];
            $map[MongodbDriver::class] = ['supportedSchemeProtocols' => ['mongodb'], 'requiredSchemeExtensions' => []];
            $map[DbalDriver::class] = [
                'supportedSchemeProtocols' => [
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
            ];

            self::$knownDrivers = $map;
        }

        return self::$knownDrivers;
    }

    public static function addDriver(string $driverClass, array $schemes, array $requiredExtensions): void
    {
        if (class_exists($driverClass)) {
            throw new \InvalidArgumentException(sprintf('The driver class "%s" does not exist.', $driverClass));
        }

        if (false == (new \ReflectionClass($driverClass))->implementsInterface(DriverInterface::class)) {
            throw new \InvalidArgumentException(sprintf('The driver class "%s" must implement "%s" interface.', $driverClass, DriverInterface::class));
        }

        if (empty($schemes)) {
            throw new \InvalidArgumentException('Schemes could not be empty');
        }
        if (empty($package)) {
            throw new \InvalidArgumentException('Package name could not be empty');
        }

        self::getKnownDrivers();
        self::$knownDrivers[$driverClass] = ['supportedSchemeProtocols' => $schemes, 'requiredSchemeExtensions' => $requiredExtensions];
    }
}
