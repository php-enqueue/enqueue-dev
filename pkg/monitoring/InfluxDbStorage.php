<?php

namespace Enqueue\Monitoring;

use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Point;

class InfluxDbStorage implements StatsStorage
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var string
     */
    private $dbName;

    /**
     * @var string
     */
    private $measurementMessages;

    /**
     * @var string
     */
    private $measurementConsumers;

    /**
     * @var Database
     */
    private $database;

    /**
     * The config could be an array, string DSN or null. In case of null it will attempt to connect to localhost.
     *
     * $config = [
     *   'dsn'                 => 'wamp://127.0.0.1:9090',
     *   'host'                => '127.0.0.1',
     *   'port'                => '9090',
     *   'topic'               => 'stats',
     *   'max_retries'         => 15,
     *   'initial_retry_delay' => 1.5,
     *   'max_retry_delay'     => 300,
     *   'retry_delay_growth'  => 1.5,
     * ]
     *
     * or
     *
     * wamp://127.0.0.1:9090?max_retries=10
     *
     * @param array|string|null $config
     */
    public function __construct($config = 'influxdb:')
    {
        $this->client = $client;
        $this->dbName = $dbName;
        $this->measurementMessages = 'messages';
        $this->measurementConsumers = 'consumers';

        if (false == class_exists(Client::class)) {
            throw new \LogicException('Seems client library is not installed. Please install "influxdb/influxdb-php"');
        }

        if (empty($config)) {
            $config = $this->parseDsn('influxdb:');
        } elseif (is_string($config)) {
            $config = $this->parseDsn($config);
        } elseif (is_array($config)) {
            $config = empty($config['dsn']) ? $config : $this->parseDsn($config['dsn']);
        } else {
            throw new \LogicException('The config must be either an array of options, a DSN string or null');
        }

        $config = array_replace([
            'host' => '127.0.0.1',
            'port' => '9090',
            'topic' => 'stats',
            'max_retries' => 15,
            'initial_retry_delay' => 1.5,
            'max_retry_delay' => 300,
            'retry_delay_growth' => 1.5,
        ], $config);

        $this->config = $config;
    }

    public function pushConsumerStats(ConsumerStats $event): void
    {
        $points = [];

        foreach ($event->getQueues() as $queue) {
            $tags = [
                'queue' => $queue,
                'consumerId' => $event->getConsumerId(),
            ];

            $values = [
                'startedAtMs' => $event->getStartedAtMs(),
                'started' => $event->isStarted(),
                'finished' => $event->isFinished(),
                'failed' => $event->isFailed(),
                'received' => $event->getReceived(),
                'acknowledged' => $event->getAcknowledged(),
                'rejected' => $event->getRejected(),
                'requeued' => $event->getRequeued(),
                'memoryUsage' => $event->getMemoryUsage(),
                'systemLoad' => $event->getSystemLoad(),
            ];

            if ($event->getFinishedAtMs()) {
                $values['finishedAtMs'] = $event->getFinishedAtMs();
            }

            $points[] = new Point($this->measurementConsumers, null, $tags, $values, $event->getTimestampMs());
        }

        $this->getDb()->writePoints($points, Database::PRECISION_MILLISECONDS);
    }

    public function pushMessageStats(MessageStats $event): void
    {
        $tags = [
            'queue' => $event->getQueue(),
            'status' => $event->getStatus(),
        ];

        $values = [
            'receivedAt' => $event->getReceivedAtMs(),
            'processedAt' => $event->getTimestampMs(),
        ];

        if (MessageStats::STATUS_FAILED === $event->getStatus()) {
            $values['failed'] = 1;
        }

        $runtime = $event->getTimestampMs() - $event->getReceivedAtMs();

        $points = [
            new Point($this->measurementMessages, $runtime, $tags, $values, $event->getTimestampMs()),
        ];

        $this->getDb()->writePoints($points, Database::PRECISION_MILLISECONDS);
    }

    private function getDb(): Database
    {
        if (null === $this->database) {
            $this->database = $this->client->selectDB($this->dbName);
            $this->database->create();
        }

        return $this->database;
    }

    private function parseDsn(string $dsn): array
    {
        $dsn = new Dsn($dsn);

        if (false === in_array($dsn->getSchemeProtocol(), ['wamp', 'ws'], true)) {
            throw new \LogicException(sprintf(
                'The given scheme protocol "%s" is not supported. It must be "wamp"',
                $dsn->getSchemeProtocol()
            ));
        }

        return array_filter(array_replace($dsn->getQuery(), [
            'host' => $dsn->getHost(),
            'port' => $dsn->getPort(),
            'topic' => $dsn->getQueryParameter('topic'),
            'max_retries' => $dsn->getInt('max_retries'),
            'initial_retry_delay' => $dsn->getFloat('initial_retry_delay'),
            'max_retry_delay' => $dsn->getInt('max_retry_delay'),
            'retry_delay_growth' => $dsn->getFloat('retry_delay_growth'),
        ]), function ($value) { return null !== $value; });
    }
}
