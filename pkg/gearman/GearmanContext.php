<?php

namespace Enqueue\Gearman;

use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrSubscriptionConsumer;
use Interop\Queue\PsrTopic;
use Interop\Queue\PurgeQueueNotSupportedException;
use Interop\Queue\SubscriptionConsumerNotSupportedException;
use Interop\Queue\TemporaryQueueNotSupportedException;

class GearmanContext implements PsrContext
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
     * @return GearmanMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): PsrMessage
    {
        return new GearmanMessage($body, $properties, $headers);
    }

    /**
     * @return GearmanDestination
     */
    public function createTopic(string $topicName): PsrTopic
    {
        return new GearmanDestination($topicName);
    }

    /**
     * @return GearmanDestination
     */
    public function createQueue(string $queueName): PsrQueue
    {
        return new GearmanDestination($queueName);
    }

    public function createTemporaryQueue(): PsrQueue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return GearmanProducer
     */
    public function createProducer(): PsrProducer
    {
        return new GearmanProducer($this->getClient());
    }

    /**
     * @param GearmanDestination $destination
     *
     * @return GearmanConsumer
     */
    public function createConsumer(PsrDestination $destination): PsrConsumer
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

    public function createSubscriptionConsumer(): PsrSubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    public function purgeQueue(PsrQueue $queue): void
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
