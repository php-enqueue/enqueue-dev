<?php

namespace Enqueue\Sqs;

use Aws\Sqs\SqsClient;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrSubscriptionConsumer;
use Interop\Queue\PsrTopic;
use Interop\Queue\SubscriptionConsumerNotSupportedException;
use Interop\Queue\TemporaryQueueNotSupportedException;

class SqsContext implements PsrContext
{
    /**
     * @var SqsClient
     */
    private $client;

    /**
     * @var callable
     */
    private $clientFactory;

    /**
     * @var array
     */
    private $queueUrls;

    /**
     * Callable must return instance of SqsClient once called.
     *
     * @param SqsClient|callable $client
     */
    public function __construct($client)
    {
        if ($client instanceof SqsClient) {
            $this->client = $client;
        } elseif (is_callable($client)) {
            $this->clientFactory = $client;
        } else {
            throw new \InvalidArgumentException(sprintf(
                'The $client argument must be either %s or callable that returns %s once called.',
                SqsClient::class,
                SqsClient::class
            ));
        }
    }

    /**
     * @return SqsMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): PsrMessage
    {
        return new SqsMessage($body, $properties, $headers);
    }

    /**
     * @return SqsDestination
     */
    public function createTopic(string $topicName): PsrTopic
    {
        return new SqsDestination($topicName);
    }

    /**
     * @return SqsDestination
     */
    public function createQueue(string $queueName): PsrQueue
    {
        return new SqsDestination($queueName);
    }

    public function createTemporaryQueue(): PsrQueue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return SqsProducer
     */
    public function createProducer(): PsrProducer
    {
        return new SqsProducer($this);
    }

    /**
     * @param SqsDestination $destination
     *
     * @return SqsConsumer
     */
    public function createConsumer(PsrDestination $destination): PsrConsumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, SqsDestination::class);

        return new SqsConsumer($this, $destination);
    }

    public function close(): void
    {
    }

    /**
     * @param SqsDestination $queue
     */
    public function purgeQueue(PsrQueue $queue): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, SqsDestination::class);

        $this->getClient()->purgeQueue([
            'QueueUrl' => $this->getQueueUrl($queue),
        ]);
    }

    public function createSubscriptionConsumer(): PsrSubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    public function getClient(): SqsClient
    {
        if (false == $this->client) {
            $client = call_user_func($this->clientFactory);
            if (false == $client instanceof SqsClient) {
                throw new \LogicException(sprintf(
                    'The factory must return instance of "%s". But it returns %s',
                    SqsClient::class,
                    is_object($client) ? get_class($client) : gettype($client)
                ));
            }

            $this->client = $client;
        }

        return $this->client;
    }

    public function getQueueUrl(SqsDestination $destination): string
    {
        if (isset($this->queueUrls[$destination->getQueueName()])) {
            return $this->queueUrls[$destination->getQueueName()];
        }

        $result = $this->getClient()->getQueueUrl([
            'QueueName' => $destination->getQueueName(),
        ]);

        if (false == $result->hasKey('QueueUrl')) {
            throw new \RuntimeException(sprintf('QueueUrl cannot be resolved. queueName: "%s"', $destination->getQueueName()));
        }

        return $this->queueUrls[$destination->getQueueName()] = $result->get('QueueUrl');
    }

    public function declareQueue(SqsDestination $dest): void
    {
        $result = $this->getClient()->createQueue([
            'Attributes' => $dest->getAttributes(),
            'QueueName' => $dest->getQueueName(),
        ]);

        if (false == $result->hasKey('QueueUrl')) {
            throw new \RuntimeException(sprintf('Cannot create queue. queueName: "%s"', $dest->getQueueName()));
        }

        $this->queueUrls[$dest->getQueueName()] = $result->get('QueueUrl');
    }

    public function deleteQueue(SqsDestination $dest): void
    {
        $this->getClient()->deleteQueue([
            'QueueUrl' => $this->getQueueUrl($dest),
        ]);

        unset($this->queueUrls[$dest->getQueueName()]);
    }
}
