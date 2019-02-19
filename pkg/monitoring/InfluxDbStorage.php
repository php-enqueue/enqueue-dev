<?php

namespace Enqueue\Monitoring;

use Enqueue\Client\Config;
use Enqueue\Dsn\Dsn;
use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Point;

class InfluxDbStorage implements StatsStorage
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Database
     */
    private $database;

    /**
     * The config could be an array, string DSN, null or instance of InfluxDB\Client. In case of null it will attempt to connect to localhost.
     *
     * $config = [
     *   'dsn' => 'influxdb://127.0.0.1:8086',
     *   'host' => '127.0.0.1',
     *   'port' => '8086',
     *   'user' => '',
     *   'password' => '',
     *   'db' => 'enqueue',
     *   'measurementSentMessages' => 'sent-messages',
     *   'measurementConsumedMessages' => 'consumed-messages',
     *   'measurementConsumers' => 'consumers',
     * ]
     *
     * or
     *
     * influxdb://127.0.0.1:8086?user=Jon&password=secret
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'influxdb:')
    {
        if (false == class_exists(Client::class)) {
            throw new \LogicException('Seems client library is not installed. Please install "influxdb/influxdb-php"');
        }

        if (empty($config)) {
            $config = [];
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
            $config = empty($config['dsn']) ? $config : $this->parseDsn($config['dsn']);
        } elseif ($config instanceof Client) {
            $this->client = $config;
            $config = [];
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $config = array_replace([
            'host' => '127.0.0.1',
            'port' => '8086',
            'user' => '',
            'password' => '',
            'db' => 'enqueue',
            'measurementSentMessages' => 'sent-messages',
            'measurementConsumedMessages' => 'consumed-messages',
            'measurementConsumers' => 'consumers',
        ], $config);

        $this->config = $config;
    }

    public function pushConsumerStats(ConsumerStats $stats): void
    {
        $points = [];

        foreach ($stats->getQueues() as $queue) {
            $tags = [
                'queue' => $queue,
                'consumerId' => $stats->getConsumerId(),
            ];

            $values = [
                'startedAtMs' => $stats->getStartedAtMs(),
                'started' => $stats->isStarted(),
                'finished' => $stats->isFinished(),
                'failed' => $stats->isFailed(),
                'received' => $stats->getReceived(),
                'acknowledged' => $stats->getAcknowledged(),
                'rejected' => $stats->getRejected(),
                'requeued' => $stats->getRequeued(),
                'memoryUsage' => $stats->getMemoryUsage(),
                'systemLoad' => $stats->getSystemLoad(),
            ];

            if ($stats->getFinishedAtMs()) {
                $values['finishedAtMs'] = $stats->getFinishedAtMs();
            }

            $points[] = new Point($this->config['measurementConsumers'], null, $tags, $values, $stats->getTimestampMs());
        }

        $this->getDb()->writePoints($points, Database::PRECISION_MILLISECONDS);
    }

    public function pushConsumedMessageStats(ConsumedMessageStats $stats): void
    {
        $tags = [
            'queue' => $stats->getQueue(),
            'status' => $stats->getStatus(),
        ];

        $values = [
            'receivedAt' => $stats->getReceivedAtMs(),
            'processedAt' => $stats->getTimestampMs(),
            'redelivered' => $stats->isRedelivered(),
        ];

        if (ConsumedMessageStats::STATUS_FAILED === $stats->getStatus()) {
            $values['failed'] = 1;
        }

        $runtime = $stats->getTimestampMs() - $stats->getReceivedAtMs();

        $points = [
            new Point($this->config['measurementConsumedMessages'], $runtime, $tags, $values, $stats->getTimestampMs()),
        ];

        $this->getDb()->writePoints($points, Database::PRECISION_MILLISECONDS);
    }

    public function pushSentMessageStats(SentMessageStats $stats): void
    {
        $tags = [
            'destination' => $stats->getDestination(),
        ];

        $properties = $stats->getProperties();

        if (false === empty($properties[Config::TOPIC])) {
            $tags['topic'] = $properties[Config::TOPIC];
        }

        if (false === empty($properties[Config::COMMAND])) {
            $tags['command'] = $properties[Config::COMMAND];
        }

        $points = [
            new Point($this->config['measurementSentMessages'], 1, $tags, [], $stats->getTimestampMs()),
        ];

        $this->getDb()->writePoints($points, Database::PRECISION_MILLISECONDS);
    }

    private function getDb(): Database
    {
        if (null === $this->database) {
            if (null === $this->client) {
                $this->client = new Client(
                    $this->config['host'],
                    $this->config['port'],
                    $this->config['user'],
                    $this->config['password']
                );
            }

            $this->database = $this->client->selectDB($this->config['db']);
            $this->database->create();
        }

        return $this->database;
    }

    private function parseDsn(string $dsn): array
    {
        $dsn = Dsn::parseFirst($dsn);

        if (false === in_array($dsn->getSchemeProtocol(), ['influxdb'], true)) {
            throw new \LogicException(sprintf(
                'The given scheme protocol "%s" is not supported. It must be "influxdb"',
                $dsn->getSchemeProtocol()
            ));
        }

        return array_filter(array_replace($dsn->getQuery(), [
            'host' => $dsn->getHost(),
            'port' => $dsn->getPort(),
            'user' => $dsn->getUser(),
            'password' => $dsn->getPassword(),
            'db' => $dsn->getString('db'),
            'measurementSentMessages' => $dsn->getString('measurementSentMessages'),
            'measurementConsumedMessages' => $dsn->getString('measurementConsumedMessages'),
            'measurementConsumers' => $dsn->getString('measurementConsumers'),
        ]), function ($value) { return null !== $value; });
    }
}
