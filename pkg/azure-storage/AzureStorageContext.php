<?php
declare(strict_types=1);

namespace Enqueue\AzureStorage;

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
use MicrosoftAzure\Storage\Queue\QueueRestProxy;

class AzureStorageContext implements Context
{
    /**
     * @var QueueRestProxy
     */
    protected $client;

    public function __construct(QueueRestProxy $client)
    {
        $this->client = $client;
    }

    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        $message = new AzureStorageMessage();
        $message->setBody($body);
        $message->setProperties($properties);
        $message->setHeaders($headers);
        return $message;
    }

    public function createTopic(string $topicName): Topic
    {
        return new AzureStorageDestination($topicName);
    }

    public function createQueue(string $queueName): Queue
    {
        return new AzureStorageDestination($queueName);
    }


    /**
     * @param AzureStorageDestination $queue
     */
    public function deleteQueue(Queue $queue): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, AzureStorageDestination::class);

        $this->client->deleteQueue($queue);
    }

    /**
     * @param AzureStorageDestination $topic
     */
    public function deleteTopic(Topic $topic): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($topic, AzureStorageDestination::class);

        $this->client->deleteQueue($topic);
    }

    /**
     * @inheritdoc
     */
    public function createTemporaryQueue(): Queue
    {
        throw new TemporaryQueueNotSupportedException();
    }

    public function createProducer(): Producer
    {
        return new AzureStorageProducer($this->client);
    }

    /**
     * @param AzureStorageDestination $destination
     * @return Consumer
     * @throws InvalidDestinationException
     */
    public function createConsumer(Destination $destination): Consumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AzureStorageDestination::class);

        return new AzureStorageConsumer($this->client, $destination, $this);
    }

    /**
     * @inheritdoc
     */
    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        throw new SubscriptionConsumerNotSupportedException();
    }

    /**
     * @inheritdoc
     */
    public function purgeQueue(Queue $queue): void
    {
        throw new PurgeQueueNotSupportedException();
    }

    public function close(): void
    {}
}