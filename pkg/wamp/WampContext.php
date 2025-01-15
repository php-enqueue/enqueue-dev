<?php

declare(strict_types=1);

namespace Enqueue\Wamp;

use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\PurgeQueueNotSupportedException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;
use Thruway\Peer\Client;

class WampContext implements Context
{
    use SerializerAwareTrait;

    /**
     * @var Client[]
     */
    private $clients;

    /**
     * @var callable
     */
    private $clientFactory;

    public function __construct(callable $clientFactory)
    {
        $this->clientFactory = $clientFactory;

        $this->setSerializer(new JsonSerializer());
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
        throw PurgeQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public function close(): void
    {
        foreach ($this->clients as $client) {
            if (null === $client->getSession()) {
                return;
            }

            $client->setAttemptRetry(false);
            $client->getSession()->close();
        }
    }

    public function getNewClient(): Client
    {
        $client = call_user_func($this->clientFactory);

        if (false == $client instanceof Client) {
            throw new \LogicException(sprintf('The factory must return instance of "%s". But it returns %s', Client::class, is_object($client) ? $client::class : gettype($client)));
        }

        $this->clients[] = $client;

        return $client;
    }
}
