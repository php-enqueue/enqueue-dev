<?php

namespace Enqueue\Null;

use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrSubscriptionConsumer;
use Interop\Queue\PsrTopic;

class NullContext implements PsrContext
{
    /**
     * @return NullMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): PsrMessage
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
    public function createQueue(string $name): PsrQueue
    {
        return new NullQueue($name);
    }

    /**
     * @return NullQueue
     */
    public function createTemporaryQueue(): PsrQueue
    {
        return $this->createQueue(uniqid('', true));
    }

    /**
     * @return NullTopic
     */
    public function createTopic(string $name): PsrTopic
    {
        return new NullTopic($name);
    }

    /**
     * @return NullConsumer
     */
    public function createConsumer(PsrDestination $destination): PsrConsumer
    {
        return new NullConsumer($destination);
    }

    /**
     * @return NullProducer
     */
    public function createProducer(): PsrProducer
    {
        return new NullProducer();
    }

    /**
     * @return NullSubscriptionConsumer
     */
    public function createSubscriptionConsumer(): PsrSubscriptionConsumer
    {
        return new NullSubscriptionConsumer();
    }

    public function purgeQueue(PsrQueue $queue): void
    {
    }

    public function close(): void
    {
    }
}
