<?php

declare(strict_types=1);

namespace Enqueue\Gearman;

use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\PurgeQueueNotSupportedException;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;

class GearmanContext implements Context
{
    /**
     * @var \GearmanClient
     */
    private $client;

    /**
     * @var GearmanConsumer[]
     */
    private $consumers;

    /**
     * @var array
     */
    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param mixed $body
     *
     * @return GearmanMessage
     */
    public function createMessage($body = '', array $properties = [], array $headers = []): Message
    {
        return new GearmanMessage($body, $properties, $headers);
    }

    /**
     * @return GearmanDestination
     */
    public function createTopic(string $topicName): Topic
    {
        return new GearmanDestination($topicName);
    }

    /**
     * @return GearmanDestination
     */
    public function createQueue(string $queueName): Queue
    {
        return new GearmanDestination($queueName);
    }

    public function createTemporaryQueue(): Queue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return GearmanProducer
     */
    public function createProducer(): Producer
    {
        return new GearmanProducer($this->getClient());
    }

    /**
     * @param GearmanDestination $destination
     *
     * @return GearmanConsumer
     */
    public function createConsumer(Destination $destination): Consumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, GearmanDestination::class);

        $this->consumers[] = $consumer = new GearmanConsumer($this, $destination);

        return $consumer;
    }

    public function close(): void
    {
        $this->getClient()->clearCallbacks();

        foreach ($this->consumers as $consumer) {
            $consumer->getWorker()->unregisterAll();
        }
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    public function purgeQueue(Queue $queue): void
    {
        throw PurgeQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public function getClient(): \GearmanClient
    {
        if (false == $this->client) {
            $this->client = new \GearmanClient();
            $this->client->addServer($this->config['host'], $this->config['port']);
        }

        return $this->client;
    }

    public function createWorker(): \GearmanWorker
    {
        $worker = new \GearmanWorker();
        $worker->addServer($this->config['host'], $this->config['port']);

        return $worker;
    }
}
