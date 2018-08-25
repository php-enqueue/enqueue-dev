<?php

namespace Enqueue\Mongodb;

use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrSubscriptionConsumer;
use Interop\Queue\PsrTopic;
use Interop\Queue\SubscriptionConsumerNotSupportedException;
use Interop\Queue\TemporaryQueueNotSupportedException;
use MongoDB\Client;
use MongoDB\Collection;

class MongodbContext implements PsrContext
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
     * @return MongodbMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): PsrMessage
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
    public function createTopic(string $name): PsrTopic
    {
        return new MongodbDestination($name);
    }

    /**
     * @return MongodbDestination
     */
    public function createQueue(string $queueName): PsrQueue
    {
        return new MongodbDestination($queueName);
    }

    public function createTemporaryQueue(): PsrQueue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return MongodbProducer
     */
    public function createProducer(): PsrProducer
    {
        return new MongodbProducer($this);
    }

    /**
     * @param MongodbDestination $destination
     *
     * @return MongodbConsumer
     */
    public function createConsumer(PsrDestination $destination): PsrConsumer
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

    public function createSubscriptionConsumer(): PsrSubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @param MongodbDestination $queue
     */
    public function purgeQueue(PsrQueue $queue): void
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
        $collection->createIndex(['priority' => -1, 'published_at' => 1], ['name' => 'enqueue_priority']);
        $collection->createIndex(['delayed_until' => 1], ['name' => 'enqueue_delayed']);
    }
}
