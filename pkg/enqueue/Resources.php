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
use Enqueue\SnsQs\SnsQsConnectionFactory;
use Enqueue\Sqs\SqsConnectionFactory;
use Enqueue\Stomp\StompConnectionFactory;
use Enqueue\Wamp\WampConnectionFactory;
use Interop\Queue\ConnectionFactory;

final class Resources
{
    /**
     * [connection factory class => [
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

            $map[FsConnectionFactory::class] = [
                'schemes' => ['file'],
                'supportedSchemeExtensions' => [],
                'package' => 'enqueue/fs',
            ];
            $map[AmqpBunnyConnectionFactory::class] = [
                'schemes' => ['amqp'],
                'supportedSchemeExtensions' => ['bunny'],
                'package' => 'enqueue/amqp-bunny',
            ];
            $map[AmqpExtConnectionFactory::class] = [
                'schemes' => ['amqp', 'amqps'],
                'supportedSchemeExtensions' => ['ext'],
                'package' => 'enqueue/amqp-ext',
            ];
            $map[AmqpLibConnectionFactory::class] = [
                'schemes' => ['amqp', 'amqps'],
                'supportedSchemeExtensions' => ['lib'],
                'package' => 'enqueue/amqp-lib',
            ];

            $map[DbalConnectionFactory::class] = [
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
                    'sqlite',
                    'sqlite3',
                    'sqlite',
                ],
                'supportedSchemeExtensions' => ['pdo'],
                'package' => 'enqueue/dbal',
            ];

            $map[NullConnectionFactory::class] = [
                'schemes' => ['null'],
                'supportedSchemeExtensions' => [],
                'package' => 'enqueue/null',
            ];
            $map[GearmanConnectionFactory::class] = [
                'schemes' => ['gearman'],
                'supportedSchemeExtensions' => [],
                'package' => 'enqueue/gearman',
            ];
            $map[PheanstalkConnectionFactory::class] = [
                'schemes' => ['beanstalk'],
                'supportedSchemeExtensions' => ['pheanstalk'],
                'package' => 'enqueue/pheanstalk',
            ];
            $map[RdKafkaConnectionFactory::class] = [
                'schemes' => ['kafka', 'rdkafka'],
                'supportedSchemeExtensions' => ['rdkafka'],
                'package' => 'enqueue/rdkafka',
            ];
            $map[RedisConnectionFactory::class] = [
                'schemes' => ['redis', 'rediss'],
                'supportedSchemeExtensions' => ['predis', 'phpredis'],
                'package' => 'enqueue/redis',
            ];
            $map[StompConnectionFactory::class] = [
                'schemes' => ['stomp'],
                'supportedSchemeExtensions' => ['rabbitmq'],
                'package' => 'enqueue/stomp', ];
            $map[SqsConnectionFactory::class] = [
                'schemes' => ['sqs'],
                'supportedSchemeExtensions' => [],
                'package' => 'enqueue/sqs', ];
            $map[SnsQsConnectionFactory::class] = [
                'schemes' => ['snsqs'],
                'supportedSchemeExtensions' => [],
                'package' => 'enqueue/snsqs', ];
            $map[GpsConnectionFactory::class] = [
                'schemes' => ['gps'],
                'supportedSchemeExtensions' => [],
                'package' => 'enqueue/gps', ];
            $map[MongodbConnectionFactory::class] = [
                'schemes' => ['mongodb'],
                'supportedSchemeExtensions' => [],
                'package' => 'enqueue/mongodb',
            ];
            $map[WampConnectionFactory::class] = [
                'schemes' => ['wamp', 'ws'],
                'supportedSchemeExtensions' => [],
                'package' => 'enqueue/wamp',
            ];

            self::$knownConnections = $map;
        }

        return self::$knownConnections;
    }

    public static function addConnection(string $connectionFactoryClass, array $schemes, array $extensions, string $package): void
    {
        if (class_exists($connectionFactoryClass)) {
            if (false == (new \ReflectionClass($connectionFactoryClass))->implementsInterface(ConnectionFactory::class)) {
                throw new \InvalidArgumentException(sprintf('The connection factory class "%s" must implement "%s" interface.', $connectionFactoryClass, ConnectionFactory::class));
            }
        }

        if (empty($schemes)) {
            throw new \InvalidArgumentException('Schemes could not be empty.');
        }
        if (empty($package)) {
            throw new \InvalidArgumentException('Package name could not be empty.');
        }

        self::getKnownConnections();
        self::$knownConnections[$connectionFactoryClass] = [
            'schemes' => $schemes,
            'supportedSchemeExtensions' => $extensions,
            'package' => $package,
        ];
    }
}
