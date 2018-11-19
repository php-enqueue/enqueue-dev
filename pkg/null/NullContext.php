<?php

declare(strict_types=1);

namespace Enqueue\Null;

use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;

class NullContext implements Context
{
    /**
     * @return NullMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        $message = new NullMessage();
        $message->setBody($body);
        $message->setProperties($properties);
        $message->setHeaders($headers);

        return $message;
    }

    /**
     * @return NullQueue
     */
    public function createQueue(string $name): Queue
    {
        return new NullQueue($name);
    }

    /**
     * @return NullQueue
     */
    public function createTemporaryQueue(): Queue
    {
        return $this->createQueue(uniqid('', true));
    }

    /**
     * @return NullTopic
     */
    public function createTopic(string $name): Topic
    {
        return new NullTopic($name);
    }

    /**
     * @return NullConsumer
     */
    public function createConsumer(Destination $destination): Consumer
    {
        return new NullConsumer($destination);
    }

    /**
     * @return NullProducer
     */
    public function createProducer(): Producer
    {
        return new NullProducer();
    }

    /**
     * @return NullSubscriptionConsumer
     */
    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        return new NullSubscriptionConsumer();
    }

    public function purgeQueue(Queue $queue): void
    {
    }

    public function close(): void
    {
    }
}
