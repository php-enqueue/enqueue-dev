<?php

namespace Enqueue\Gps;

use Google\Cloud\Core\Exception\ConflictException;
use Google\Cloud\PubSub\PubSubClient;
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

class GpsContext implements PsrContext
{
    /**
     * @var PubSubClient
     */
    private $client;

    /**
     * @var callable
     */
    private $clientFactory;

    /**
     * @var array
     */
    private $options;

    /**
     * Callable must return instance of PubSubClient once called.
     *
     * @param PubSubClient|callable $client
     */
    public function __construct($client, array $options = [])
    {
        $this->options = array_replace([
            'ackDeadlineSeconds' => 10,
        ], $options);

        if ($client instanceof PubSubClient) {
            $this->client = $client;
        } elseif (is_callable($client)) {
            $this->clientFactory = $client;
        } else {
            throw new \InvalidArgumentException(sprintf(
                'The $client argument must be either %s or callable that returns %s once called.',
                PubSubClient::class,
                PubSubClient::class
            ));
        }
    }

    /**
     * @return GpsMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): PsrMessage
    {
        return new GpsMessage($body, $properties, $headers);
    }

    /**
     * @return GpsTopic
     */
    public function createTopic(string $topicName): PsrTopic
    {
        return new GpsTopic($topicName);
    }

    /**
     * @return GpsQueue
     */
    public function createQueue(string $queueName): PsrQueue
    {
        return new GpsQueue($queueName);
    }

    public function createTemporaryQueue(): PsrQueue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return GpsProducer
     */
    public function createProducer(): PsrProducer
    {
        return new GpsProducer($this);
    }

    /**
     * @param GpsQueue|GpsTopic $destination
     *
     * @return GpsConsumer
     */
    public function createConsumer(PsrDestination $destination): PsrConsumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, GpsQueue::class);

        return new GpsConsumer($this, $destination);
    }

    public function close(): void
    {
    }

    public function createSubscriptionConsumer(): PsrSubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    public function purgeQueue(PsrQueue $queue): void
    {
        throw PurgeQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public function declareTopic(GpsTopic $topic): void
    {
        try {
            $this->getClient()->createTopic($topic->getTopicName());
        } catch (ConflictException $e) {
        }
    }

    public function subscribe(GpsTopic $topic, GpsQueue $queue): void
    {
        $this->declareTopic($topic);

        try {
            $this->getClient()->subscribe($queue->getQueueName(), $topic->getTopicName(), [
                'ackDeadlineSeconds' => $this->options['ackDeadlineSeconds'],
            ]);
        } catch (ConflictException $e) {
        }
    }

    public function getClient(): PubSubClient
    {
        if (false == $this->client) {
            $client = call_user_func($this->clientFactory);
            if (false == $client instanceof PubSubClient) {
                throw new \LogicException(sprintf(
                    'The factory must return instance of %s. It returned %s',
                    PubSubClient::class,
                    is_object($client) ? get_class($client) : gettype($client)
                ));
            }

            $this->client = $client;
        }

        return $this->client;
    }
}
