<?php

/*
    Copyright (c) 2024 g41797
    SPDX-License-Identifier: MIT
*/

namespace Enqueue\Natsjs;

use Basis\Nats\Client;
use Basis\Nats\Connection;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;

class NatsjsContext implements Context
{
    private Client $broker;

    public function __construct(Client $broker)
    {
        $this->broker = $broker;
    }

    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        return new NatsjsMessage($body, $properties, $headers);
    }

    public function createTopic(string $topicName): Topic
    {
        return new NatsjsDestination($topicName);
    }

    public function createQueue(string $queueName): Queue
    {
        return new NatsjsDestination($queueName);
    }

    public function createTemporaryQueue(): Queue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public function createProducer(): Producer
    {
        // TODO: Implement createProducer() method.
    }

    public function createConsumer(Destination $destination): Consumer
    {
        // TODO: Implement createConsumer() method.
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    public function purgeQueue(Queue $queue): void
    {
        $this->broker->getApi()->getStream($queue->getQueueName())->purge();
    }

    public function close(): void
    {
        if ($this->broker->ping()) {
            $property = new \ReflectionProperty(Connection::class, 'socket');
            fclose($property->getValue($this->broker->connection));
        }
    }
}
