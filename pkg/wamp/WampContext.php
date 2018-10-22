<?php

declare(strict_types=1);

namespace Enqueue\Wamp;

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
use Thruway\Peer\Client;

class WampContext implements Context
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var callable
     */
    private $clientFactory;

    public function __construct($client)
    {
        if ($client instanceof Client) {
            $this->client = $client;
        } elseif (is_callable($client)) {
            $this->clientFactory = $client;
        } else {
            throw new \InvalidArgumentException(sprintf(
                'The $client argument must be either %s or callable that returns %s once called.',
                Client::class,
                Client::class
            ));
        }
    }

    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        return new WampMessage($body, $properties, $headers);
    }

    public function createTopic(string $topicName): Topic
    {
        return new WampDestination($topicName);
    }

    public function createQueue(string $queueName): Queue
    {
        return new WampDestination($queueName);
    }

    public function createTemporaryQueue(): Queue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public function createProducer(): Producer
    {
        return new WampProducer($this);
    }

    public function createConsumer(Destination $destination): Consumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, WampDestination::class);

        return new WampConsumer($this, $destination);
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        return new WampSubscriptionConsumer($this);
    }

    public function purgeQueue(Queue $queue): void
    {
    }

    public function close(): void
    {
        if (null === $this->client) {
            return;
        }

        if (null === $this->client->getSession()) {
            return;
        }

        $this->client->setAttemptRetry(false);
        $this->client->getSession()->close();
    }

    public function getClient(): Client
    {
        if (false == $this->client) {
            $client = call_user_func($this->clientFactory);
            if (false == $client instanceof Client) {
                throw new \LogicException(sprintf(
                    'The factory must return instance of "%s". But it returns %s',
                    Client::class,
                    is_object($client) ? get_class($client) : gettype($client)
                ));
            }

            $this->client = $client;
        }

        return $this->client;
    }
}
