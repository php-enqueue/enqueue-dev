<?php

declare(strict_types=1);

namespace Enqueue\Sqs;

use AsyncAws\Sqs\Result\CreateQueueResult;
use AsyncAws\Sqs\Result\GetQueueAttributesResult;
use AsyncAws\Sqs\Result\GetQueueUrlResult;
use AsyncAws\Sqs\Result\ReceiveMessageResult;
use AsyncAws\Sqs\Result\SendMessageResult;
use AsyncAws\Sqs\SqsClient;

/**
 * @internal
 */
class SqsAsyncClient
{
    private $client;

    public function __construct(SqsClient $client)
    {
        $this->client = $client;
    }

    public function deleteMessage(array $args): void
    {
        $this->client->deleteMessage($args);
    }

    public function receiveMessage(array $args): ReceiveMessageResult
    {
        return $this->client->receiveMessage($args);
    }

    public function changeMessageVisibility(array $args): void
    {
        $this->client->changeMessageVisibility($args);
    }

    public function purgeQueue(array $args): void
    {
        $this->client->purgeQueue($args);
    }

    public function getQueueUrl(array $args): GetQueueUrlResult
    {
        return $this->client->getQueueUrl($args);
    }

    public function getQueueAttributes(array $args): GetQueueAttributesResult
    {
        return $this->client->getQueueAttributes($args);
    }

    public function createQueue(array $args): CreateQueueResult
    {
        return $this->client->createQueue($args);
    }

    public function deleteQueue(array $args): void
    {
        $this->client->deleteQueue($args);
    }

    public function sendMessage(array $args): SendMessageResult
    {
        return $this->client->sendMessage($args);
    }
}
