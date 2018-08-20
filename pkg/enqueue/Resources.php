<?php

namespace Enqueue;

use Enqueue\AmqpBunny\AmqpConnectionFactory as AmqpBunnyConnectionFactory;
use Enqueue\AmqpExt\AmqpConnectionFactory as AmqpExtConnectionFactory;
use Enqueue\AmqpLib\AmqpConnectionFactory as AmqpLibConnectionFactory;
use Enqueue\Dbal\DbalConnectionFactory;
use Enqueue\Fs\FsConnectionFactory;
use Enqueue\Gearman\GearmanConnectionFactory;
use Enqueue\Gps\GpsConnectionFactory;
use Enqueue\Mongodb\MongodbConnectionFactory;
use Enqueue\Null\NullConnectionFactory;
use Enqueue\Pheanstalk\PheanstalkConnectionFactory;
use Enqueue\RdKafka\RdKafkaConnectionFactory;
use Enqueue\Redis\RedisConnectionFactory;
use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Stomp\StompConnectionFactory;
use Interop\Queue\PsrConnectionFactory;

final class Resources
{
    /**
     * [psr connection factory class => [
     *   schemes => [schemes strings],
     *   package => package name,
     * ].
     *
     * @var array
     */
    private static $knownConnections = null;

    private function __construct()
    {
    }

    public static function getAvailableConnections(): array
    {
        $map = self::getKnownConnections();

        $availableMap = [];
        foreach ($map as $connectionClass => $item) {
            if (class_exists($connectionClass)) {
                $availableMap[$connectionClass] = $item;
            }
        }

        return $availableMap;
    }

    public static function getKnownSchemes(): array
    {
        $map = self::getKnownConnections();

        $schemes = [];
        foreach ($map as $connectionClass => $item) {
            foreach ($item['schemes'] as $scheme) {
                $schemes[$scheme] = $connectionClass;
            }
        }

        return $schemes;
    }

    public static function getAvailableSchemes(): array
    {
        $map = self::getAvailableConnections();

        $schemes = [];
        foreach ($map as $connectionClass => $item) {
            foreach ($item['schemes'] as $scheme) {
                $schemes[$scheme] = $connectionClass;
            }
        }

        return $schemes;
    }

    public static function getKnownConnections(): array
    {
        if (null === self::$knownConnections) {
            $map = [];

            $map[FsConnectionFactory::class] = ['schemes' => ['file'], 'package' => 'enqueue/fs'];
            $map[AmqpExtConnectionFactory::class] = [
                'schemes' => ['amqp+ext', 'amqp', 'amqps', 'amqps+ext', 'amqp+rabbitmq', 'amqps+rabbitmq', 'amqp+rabbitmq+ext', 'amqps+rabbitmq+ext'],
                'package' => 'enqueue/amqp-ext',
            ];
            $map[AmqpBunnyConnectionFactory::class] = [
                'schemes' => ['amqp+bunny', 'amqp', 'rabbitmq', 'amqp+rabbitmq', 'amqp+rabbitmq+bunny'],
                'package' => 'enqueue/amqp-bunny',
            ];
            $map[AmqpLibConnectionFactory::class] = [
                'schemes' => ['amqp+lib', 'amqp', 'amqps', 'amqps+lib', 'amqp+rabbitmq', 'amqps+rabbitmq', 'amqp+rabbitmq+lib', 'amqps+rabbitmq+lib'],
                'package' => 'enqueue/amqp-lib',
            ];

            $map[DbalConnectionFactory::class] = [
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
                'package' => 'enqueue/dbal',
            ];

            $map[NullConnectionFactory::class] = ['schemes' => ['null'], 'package' => 'enqueue/null'];
            $map[GearmanConnectionFactory::class] = ['schemes' => ['gearman'], 'package' => 'enqueue/gearman'];
            $map[PheanstalkConnectionFactory::class] = ['schemes' => ['beanstalk'], 'package' => 'enqueue/pheanstalk'];
            $map[RdKafkaConnectionFactory::class] = ['schemes' => ['kafka', 'rdkafka'], 'package' => 'enqueue/rdkafka'];
            $map[RedisConnectionFactory::class] = ['schemes' => ['redis'], 'package' => 'enqueue/redis'];
            $map[StompConnectionFactory::class] = ['schemes' => ['stomp', 'stomp+rabbitmq'], 'package' => 'enqueue/stomp'];
            $map[SqsConnectionFactory::class] = ['schemes' => ['sqs'], 'package' => 'enqueue/sqs'];
            $map[GpsConnectionFactory::class] = ['schemes' => ['gps'], 'package' => 'enqueue/gps'];
            $map[MongodbConnectionFactory::class] = ['schemes' => ['mongodb'], 'package' => 'enqueue/mongodb'];

            self::$knownConnections = $map;
        }

        return self::$knownConnections;
    }

    public static function addConnection(string $connectionFactoryClass, array $schemes, string $package): void
    {
        if (class_exists($connectionFactoryClass)) {
            throw new \InvalidArgumentException(sprintf('The connection factory class "%s" does not exist.', $connectionFactoryClass));
        }

        if (false == (new \ReflectionClass($connectionFactoryClass))->implementsInterface(PsrConnectionFactory::class)) {
            throw new \InvalidArgumentException(sprintf('The connection factory class "%s" must implement "%s" interface.', $connectionFactoryClass, PsrConnectionFactory::class));
        }

        if (empty($schemes)) {
            throw new \InvalidArgumentException('Schemes could not be empty');
        }
        if (empty($package)) {
            throw new \InvalidArgumentException('Package name could not be empty');
        }

        self::getKnownConnections();
        self::$knownConnections[$connectionFactoryClass] = ['schemes' => $schemes, 'package' => $package];
    }
}
