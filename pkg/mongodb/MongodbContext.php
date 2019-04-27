<?php

declare(strict_types=1);

namespace Enqueue\Mongodb;

use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;
use MongoDB\Client;
use MongoDB\Collection;

class MongodbContext implements Context
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var Client
     */
    private $client;

    public function __construct($client, array $config = [])
    {
        $this->config = array_replace([
            'dbname' => 'enqueue',
            'collection_name' => 'enqueue',
            'polling_interval' => null,
        ], $config);

        $this->client = $client;
    }

    /**
     * @param mixed $body
     *
     * @return MongodbMessage
     */
    public function createMessage($body = '', array $properties = [], array $headers = []): Message
    {
        $message = new MongodbMessage();
        $message->setBody($body);
        $message->setProperties($properties);
        $message->setHeaders($headers);

        return $message;
    }

    /**
     * @return MongodbDestination
     */
    public function createTopic(string $name): Topic
    {
        return new MongodbDestination($name);
    }

    /**
     * @return MongodbDestination
     */
    public function createQueue(string $queueName): Queue
    {
        return new MongodbDestination($queueName);
    }

    public function createTemporaryQueue(): Queue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return MongodbProducer
     */
    public function createProducer(): Producer
    {
        return new MongodbProducer($this);
    }

    /**
     * @param MongodbDestination $destination
     *
     * @return MongodbConsumer
     */
    public function createConsumer(Destination $destination): Consumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, MongodbDestination::class);

        $consumer = new MongodbConsumer($this, $destination);

        if (isset($this->config['polling_interval'])) {
            $consumer->setPollingInterval($this->config['polling_interval']);
        }

        return $consumer;
    }

    public function close(): void
    {
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        return new MongodbSubscriptionConsumer($this);
    }

    /**
     * @internal It must be used here and in the consumer only
     */
    public function convertMessage(array $mongodbMessage): MongodbMessage
    {
        $mongodbMessageObj = $this->createMessage(
            $mongodbMessage['body'],
            JSON::decode($mongodbMessage['properties']),
            JSON::decode($mongodbMessage['headers'])
        );

        $mongodbMessageObj->setId((string) $mongodbMessage['_id']);
        $mongodbMessageObj->setPriority((int) $mongodbMessage['priority']);
        $mongodbMessageObj->setRedelivered((bool) $mongodbMessage['redelivered']);
        $mongodbMessageObj->setPublishedAt((int) $mongodbMessage['published_at']);

        return $mongodbMessageObj;
    }

    /**
     * @param MongodbDestination $queue
     */
    public function purgeQueue(Queue $queue): void
    {
        $this->getCollection()->deleteMany([
            'queue' => $queue->getQueueName(),
        ]);
    }

    public function getCollection(): Collection
    {
        return $this->client
            ->selectDatabase($this->config['dbname'])
            ->selectCollection($this->config['collection_name']);
    }

    public function getClient(): Client
    {
        return $this->client;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function createCollection(): void
    {
        $collection = $this->getCollection();
        $collection->createIndex(['queue' => 1], ['name' => 'enqueue_queue']);
        $collection->createIndex(['priority' => -1, 'published_at' => 1], ['name' => 'enqueue_priority']);
        $collection->createIndex(['delayed_until' => 1], ['name' => 'enqueue_delayed']);
    }
}
