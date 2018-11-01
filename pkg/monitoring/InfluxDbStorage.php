<?php

namespace Enqueue\Monitoring;

use InfluxDB\Client;
use InfluxDB\Database;
use InfluxDB\Point;

class InfluxDbStorage implements EventStorage
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
     * @var Database
     */
    private $database;

    private $serializer;

    /**
     * @param Client $client
     * @param string $dbName
     */
    public function __construct(Client $client, string $dbName)
    {
        $this->client = $client;
        $this->dbName = $dbName;
        $this->measurMessages = 'msg';

        $this->serializer = new JsonSerializer();
    }

    public function onConsumerStarted(ConsumerStarted $event)
    {
//        echo $this->serializer->toString($event).PHP_EOL;
    }

    public function onConsumerStopped(ConsumerStopped $event)
    {
//        echo $this->serializer->toString($event).PHP_EOL;
    }

    public function onConsumerStats(ConsumerStats $event)
    {
//        echo $this->serializer->toString($event).PHP_EOL;
    }

    public function onMessageStats(MessageStats $event)
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
