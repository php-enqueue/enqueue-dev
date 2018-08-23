<?php

namespace Enqueue\Pheanstalk;

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
use Pheanstalk\Pheanstalk;

class PheanstalkContext implements PsrContext
{
    /**
     * @var Pheanstalk
     */
    private $pheanstalk;

    public function __construct(Pheanstalk $pheanstalk)
    {
        $this->pheanstalk = $pheanstalk;
    }

    /**
     * @return PheanstalkMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): PsrMessage
    {
        return new PheanstalkMessage($body, $properties, $headers);
    }

    /**
     * @return PheanstalkDestination
     */
    public function createTopic(string $topicName): PsrTopic
    {
        return new PheanstalkDestination($topicName);
    }

    /**
     * @return PheanstalkDestination
     */
    public function createQueue(string $queueName): PsrQueue
    {
        return new PheanstalkDestination($queueName);
    }

    public function createTemporaryQueue(): PsrQueue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return PheanstalkProducer
     */
    public function createProducer(): PsrProducer
    {
        return new PheanstalkProducer($this->pheanstalk);
    }

    /**
     * @param PheanstalkDestination $destination
     *
     * @return PheanstalkConsumer
     */
    public function createConsumer(PsrDestination $destination): PsrConsumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, PheanstalkDestination::class);

        return new PheanstalkConsumer($destination, $this->pheanstalk);
    }

    public function close(): void
    {
        $this->pheanstalk->getConnection()->disconnect();
    }

    public function createSubscriptionConsumer(): PsrSubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    public function purgeQueue(PsrQueue $queue): void
    {
        throw PurgeQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public function getPheanstalk(): Pheanstalk
    {
        return $this->pheanstalk;
    }
}
