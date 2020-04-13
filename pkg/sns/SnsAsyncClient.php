<?php

declare(strict_types=1);

namespace Enqueue\Sns;

use AsyncAws\Sns\Result\CreateTopicResponse;
use AsyncAws\Sns\Result\ListSubscriptionsByTopicResponse;
use AsyncAws\Sns\Result\PublishResponse;
use AsyncAws\Sns\Result\SubscribeResponse;
use AsyncAws\Sns\SnsClient as AwsSnsClient;

/**
 * @internal
 */
class SnsAsyncClient
{
    private $client;

    public function __construct(AwsSnsClient $client)
    {
        $this->client = $client;
    }

    public function createTopic(array $args): CreateTopicResponse
    {
        return $this->client->CreateTopic($args);
    }

    public function deleteTopic(string $topicArn): void
    {
        $this->client->DeleteTopic([
            'TopicArn' => $topicArn,
        ]);
    }

    public function publish(array $args): PublishResponse
    {
        return $this->client->Publish($args);
    }

    public function subscribe(array $args): SubscribeResponse
    {
        return $this->client->Subscribe($args);
    }

    public function unsubscribe(array $args): void
    {
        $this->client->Unsubscribe($args);
    }

    public function listSubscriptionsByTopic(array $args): ListSubscriptionsByTopicResponse
    {
        return $this->client->ListSubscriptionsByTopic($args);
    }
}
