<?php

declare(strict_types=1);

namespace Enqueue\Sqs;

use AsyncAws\Sqs\Result\CreateQueueResult;
use AsyncAws\Sqs\Result\GetQueueAttributesResult;
use AsyncAws\Sqs\Result\GetQueueUrlResult;
use AsyncAws\Sqs\Result\ReceiveMessageResult;
use AsyncAws\Sqs\Result\SendMessageResult;
use AsyncAws\Sqs\SqsClient as AwsSqsClient;

class SqsClient
{
    /**
     * @var AwsSqsClient
     */
    private $client;

    /**
     * @var AwsSqsClient|callable
     */
    private $inputClient;

    /**
     * @param AwsSqsClient|callable $inputClient
     */
    public function __construct($inputClient)
    {
        $this->inputClient = $inputClient;
    }

    public function deleteMessage(array $args): void
    {
        $this->getAWSClient()->deleteMessage($args);
    }

    public function receiveMessage(array $args): ReceiveMessageResult
    {
        return $this->getAWSClient()->receiveMessage($args);
    }

    public function changeMessageVisibility(array $args): void
    {
        $this->getAWSClient()->changeMessageVisibility($args);
    }

    public function purgeQueue(array $args): void
    {
        $this->getAWSClient()->purgeQueue($args);
    }

    public function getQueueUrl(array $args): GetQueueUrlResult
    {
        return $this->getAWSClient()->getQueueUrl($args);
    }

    public function getQueueAttributes(array $args): GetQueueAttributesResult
    {
        return $this->getAWSClient()->getQueueAttributes($args);
    }

    public function createQueue(array $args): CreateQueueResult
    {
        return $this->getAWSClient()->createQueue($args);
    }

    public function deleteQueue(array $args): void
    {
        $this->getAWSClient()->deleteQueue($args);
    }

    public function sendMessage(array $args): SendMessageResult
    {
        return $this->getAWSClient()->sendMessage($args);
    }

    public function getAWSClient(): AwsSqsClient
    {
        if ($this->client) {
            return $this->client;
        }

        $client = $this->inputClient;
        if (is_callable($client)) {
            $client = $client();
        }

        if ($client instanceof AwsSqsClient) {
            return $this->client = $client;
        }

        throw new \LogicException(sprintf(
            'The input client must be an instance of "%s" or a callable that returns it. Got "%s"',
            AwsSqsClient::class,
            is_object($client) ? get_class($client) : gettype($client)
        ));
    }
}
