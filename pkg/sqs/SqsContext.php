<?php

namespace Enqueue\Sqs;

use Aws\Sqs\SqsClient;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrContext;
use Interop\Queue\PsrDestination;

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
     * {@inheritdoc}
     *
     * @return SqsMessage
     */
    public function createMessage($body = '', array $properties = [], array $headers = [])
    {
        return new SqsMessage($body, $properties, $headers);
    }

    /**
     * {@inheritdoc}
     *
     * @return SqsDestination
     */
    public function createTopic($topicName)
    {
        return new SqsDestination($topicName);
    }

    /**
     * {@inheritdoc}
     *
     * @return SqsDestination
     */
    public function createQueue($queueName)
    {
        return new SqsDestination($queueName);
    }

    /**
     * {@inheritdoc}
     */
    public function createTemporaryQueue()
    {
        throw new \BadMethodCallException('SQS transport does not support temporary queues');
    }

    /**
     * {@inheritdoc}
     *
     * @return SqsProducer
     */
    public function createProducer()
    {
        return new SqsProducer($this);
    }

    /**
     * {@inheritdoc}
     *
     * @param SqsDestination $destination
     *
     * @return SqsConsumer
     */
    public function createConsumer(PsrDestination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, SqsDestination::class);

        return new SqsConsumer($this, $destination);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
    }

    /**
     * @return SqsClient
     */
    public function getClient()
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

    /**
     * @param SqsDestination $destination
     *
     * @return string
     */
    public function getQueueUrl(SqsDestination $destination)
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

    /**
     * @param SqsDestination $dest
     */
    public function declareQueue(SqsDestination $dest)
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

    /**
     * @param SqsDestination $dest
     */
    public function deleteQueue(SqsDestination $dest)
    {
        $this->getClient()->deleteQueue([
            'QueueUrl' => $this->getQueueUrl($dest),
        ]);

        unset($this->queueUrls[$dest->getQueueName()]);
    }

    /**
     * @deprecated since 0.8 will be removed 0.9 use self::purgeQueue()
     *
     * @param SqsDestination $dest
     */
    public function purge(SqsDestination $dest)
    {
        $this->purgeQueue($dest);
    }

    /**
     * @param SqsDestination $destination
     */
    public function purgeQueue(SqsDestination $destination)
    {
        $this->getClient()->purgeQueue([
            'QueueUrl' => $this->getQueueUrl($destination),
        ]);
    }
}
