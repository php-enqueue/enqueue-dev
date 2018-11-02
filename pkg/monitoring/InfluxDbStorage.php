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
    private $measurMessages;

    /**
     * @var string
     */
    private $measurConsumers;

    /**
     * @var Database
     */
    private $database;

    /**
     * @param Client $client
     * @param string $dbName
     */
    public function __construct(Client $client, string $dbName)
    {
        $this->client = $client;
        $this->dbName = $dbName;
        $this->measurMessages = 'messages';
        $this->measurConsumers = 'consumers';
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

            $points[] = new Point($this->measurConsumers, null, $tags, $values, $event->getTimestampMs());
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
            new Point($this->measurMessages, $runtime, $tags, $values, $event->getTimestampMs()),
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
}
