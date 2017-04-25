<?php
namespace Enqueue\Sqs;

use Aws\Sqs\SqsClient;
use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\PsrContext;
use Enqueue\Psr\PsrDestination;

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
     * @param string $queueName
     *
     * @return string
     */
    public function getQueueUrl($queueName)
    {
        if (isset($this->queueUrls[$queueName])) {
            return $this->queueUrls[$queueName];
        }

        $result = $this->getClient()->getQueueUrl([
            'QueueName' => $queueName,
        ]);

        if (false == $result->hasKey('QueueUrl')) {
            throw new \RuntimeException(sprintf('QueueUrl cannot be resolved. queueName: "%s"', $queueName));
        }

        return $this->queueUrls[$queueName] = $result->get('QueueUrl');
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
            'QueueUrl' => $this->getQueueUrl($dest->getQueueName()),
        ]);

        unset($this->queueUrls[$dest->getQueueName()]);
    }

    /**
     * @param SqsDestination $dest
     */
    public function purgeQueue(SqsDestination $dest)
    {
        $this->getClient()->purgeQueue([
            'QueueUrl' => $this->getQueueUrl($dest->getQueueName()),
        ]);
    }
}
