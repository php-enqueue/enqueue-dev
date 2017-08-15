<?php

namespace Enqueue\Gps;

use Google\Cloud\Core\Exception\ConflictException;
use Google\Cloud\PubSub\PubSubClient;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;

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
     * {@inheritdoc}
     */
    public function createMessage($body = '', array $properties = [], array $headers = [])
    {
        return new GpsMessage($body, $properties, $headers);
    }

    /**
     * {@inheritdoc}
     */
    public function createTopic($topicName)
    {
        return new GpsTopic($topicName);
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName)
    {
        return new GpsQueue($queueName);
    }

    /**
     * {@inheritdoc}
     */
    public function createTemporaryQueue()
    {
        throw new \LogicException('Not implemented');
    }

    /**
     * {@inheritdoc}
     */
    public function createProducer()
    {
        return new GpsProducer($this);
    }

    public function createConsumer(PsrDestination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, GpsQueue::class);

        return new GpsConsumer($this, $destination);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
    }

    /**
     * @param GpsTopic $topic
     */
    public function declareTopic(GpsTopic $topic)
    {
        try {
            $this->getClient()->createTopic($topic->getTopicName());
        } catch (ConflictException $e) {}
    }

    /**
     * @param GpsTopic $topic
     * @param GpsQueue $queue
     */
    public function subscribe(GpsTopic $topic, GpsQueue $queue)
    {
        $this->declareTopic($topic);

        try {
            $this->getClient()->subscribe($queue->getQueueName(), $topic->getTopicName(), [
                'ackDeadlineSeconds' => $this->options['ackDeadlineSeconds']
            ]);
        } catch (ConflictException $e) {}
    }

    /**
     * @return PubSubClient
     */
    public function getClient()
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
