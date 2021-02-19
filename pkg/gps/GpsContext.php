<?php

declare(strict_types=1);

namespace Enqueue\Gps;

use Google\Cloud\Core\Exception\ConflictException;
use Google\Cloud\PubSub\PubSubClient;
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

class GpsContext implements Context
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
     * @param mixed $body
     *
     * @return GpsMessage
     */
    public function createMessage($body = '', array $properties = [], array $headers = []): Message
    {
        return new GpsMessage($body, $properties, $headers);
    }

    /**
     * @return GpsTopic
     */
    public function createTopic(string $topicName): Topic
    {
        return new GpsTopic($topicName);
    }

    /**
     * @return GpsQueue
     */
    public function createQueue(string $queueName): Queue
    {
        return new GpsQueue($queueName);
    }

    public function createTemporaryQueue(): Queue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return GpsProducer
     */
    public function createProducer(): Producer
    {
        return new GpsProducer($this);
    }

    /**
     * @param GpsQueue|GpsTopic $destination
     *
     * @return GpsConsumer
     */
    public function createConsumer(Destination $destination): Consumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, GpsQueue::class);

        return new GpsConsumer($this, $destination);
    }

    public function close(): void
    {
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    public function purgeQueue(Queue $queue): void
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
