<?php

declare(strict_types=1);

namespace Enqueue\Sqs;

use Aws\Sqs\SqsClient;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;

class SqsContext implements Context
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

    private $config;

    /**
     * Callable must return instance of SqsClient once called.
     *
     * @param SqsClient|callable $client
     */
    public function __construct($client, array $config)
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

        $this->config = $config;
    }

    /**
     * @return SqsMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        return new SqsMessage($body, $properties, $headers);
    }

    /**
     * @return SqsDestination
     */
    public function createTopic(string $topicName): Topic
    {
        return new SqsDestination($topicName);
    }

    /**
     * @return SqsDestination
     */
    public function createQueue(string $queueName): Queue
    {
        return new SqsDestination($queueName);
    }

    public function createTemporaryQueue(): Queue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return SqsProducer
     */
    public function createProducer(): Producer
    {
        return new SqsProducer($this);
    }

    /**
     * @param SqsDestination $destination
     *
     * @return SqsConsumer
     */
    public function createConsumer(Destination $destination): Consumer
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
    public function purgeQueue(Queue $queue): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, SqsDestination::class);

        $this->getClient()->purgeQueue([
            '@region' => $queue->getRegion(),
            'QueueUrl' => $this->getQueueUrl($queue),
        ]);
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
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

        $arguments = [
            '@region' => $destination->getRegion(),
            'QueueName' => $destination->getQueueName()
        ];

        if ($destination->getQueueOwnerAWSAccountId()) {
            $arguments['QueueOwnerAWSAccountId'] = $destination->getQueueOwnerAWSAccountId();
        } elseif (false == empty($this->config['queue_owner_aws_account_id'])) {
            $arguments['QueueOwnerAWSAccountId'] = $this->config['queue_owner_aws_account_id'];
        }

        $result = $this->getClient()->getQueueUrl($arguments);

        if (false == $result->hasKey('QueueUrl')) {
            throw new \RuntimeException(sprintf('QueueUrl cannot be resolved. queueName: "%s"', $destination->getQueueName()));
        }

        return $this->queueUrls[$destination->getQueueName()] = (string) $result->get('QueueUrl');
    }

    public function declareQueue(SqsDestination $dest): void
    {
        $result = $this->getClient()->createQueue([
            '@region' => $dest->getRegion(),
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
