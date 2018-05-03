<?php

namespace Enqueue\Mongodb;

use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;
use MongoDB\Client;

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

    public function createMessage($body = '', array $properties = [], array $headers = [])
    {
        $message = new MongodbMessage();
        $message->setBody($body);
        $message->setProperties($properties);
        $message->setHeaders($headers);

        return $message;
    }

    public function createTopic($name)
    {
        return new MongodbDestination($name);
    }

    public function createQueue($queueName)
    {
        return new MongodbDestination($queueName);
    }

    public function createTemporaryQueue()
    {
        throw new \BadMethodCallException('Mongodb transport does not support temporary queues');
    }

    public function createProducer()
    {
        return new MongodbProducer($this);
    }

    public function createConsumer(PsrDestination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, MongodbDestination::class);

        $consumer = new MongodbConsumer($this, $destination);

        if (isset($this->config['polling_interval'])) {
            $consumer->setPollingInterval($this->config['polling_interval']);
        }

        return $consumer;
    }

    public function close()
    {
        // TODO: Implement close() method.
    }

    public function getCollection()
    {
        return $this->client
            ->selectDatabase($this->config['dbname'])
            ->selectCollection($this->config['collection_name']);
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    public function createCollection()
    {
        $collection = $this->getCollection();
        $collection->createIndex(['priority' => -1, 'published_at' => 1], ['name' => 'enqueue_priority']);
        $collection->createIndex(['delayed_until' => 1], ['name' => 'enqueue_delayed']);
    }
}
