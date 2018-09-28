<?php

declare(strict_types=1);

namespace Enqueue\Pheanstalk;

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
use Pheanstalk\Pheanstalk;

class PheanstalkContext implements Context
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
    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        return new PheanstalkMessage($body, $properties, $headers);
    }

    /**
     * @return PheanstalkDestination
     */
    public function createTopic(string $topicName): Topic
    {
        return new PheanstalkDestination($topicName);
    }

    /**
     * @return PheanstalkDestination
     */
    public function createQueue(string $queueName): Queue
    {
        return new PheanstalkDestination($queueName);
    }

    public function createTemporaryQueue(): Queue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return PheanstalkProducer
     */
    public function createProducer(): Producer
    {
        return new PheanstalkProducer($this->pheanstalk);
    }

    /**
     * @param PheanstalkDestination $destination
     *
     * @return PheanstalkConsumer
     */
    public function createConsumer(Destination $destination): Consumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, PheanstalkDestination::class);

        return new PheanstalkConsumer($destination, $this->pheanstalk);
    }

    public function close(): void
    {
        $this->pheanstalk->getConnection()->disconnect();
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    public function purgeQueue(Queue $queue): void
    {
        throw PurgeQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public function getPheanstalk(): Pheanstalk
    {
        return $this->pheanstalk;
    }
}
