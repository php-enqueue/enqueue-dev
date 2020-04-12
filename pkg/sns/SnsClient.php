<?php

declare(strict_types=1);

namespace Enqueue\Sns;

use AsyncAws\Sns\Result\CreateTopicResponse;
use AsyncAws\Sns\Result\ListSubscriptionsByTopicResponse;
use AsyncAws\Sns\Result\PublishResponse;
use AsyncAws\Sns\Result\SubscribeResponse;
use AsyncAws\Sns\SnsClient as AwsSnsClient;

class SnsClient
{
    /**
     * @var AwsSnsClient
     */
    private $client;

    /**
     * @var callable
     */
    private $inputClient;

    /**
     * @param AwsSnsClient|callable $inputClient
     */
    public function __construct($inputClient)
    {
        $this->inputClient = $inputClient;
    }

    public function createTopic(array $args): CreateTopicResponse
    {
        return $this->getAWSClient()->CreateTopic($args);
    }

    public function deleteTopic(string $topicArn): void
    {
        $this->getAWSClient()->DeleteTopic([
            'TopicArn' => $topicArn,
        ]);
    }

    public function publish(array $args): PublishResponse
    {
        return $this->getAWSClient()->Publish($args);
    }

    public function subscribe(array $args): SubscribeResponse
    {
        return $this->getAWSClient()->Subscribe($args);
    }

    public function unsubscribe(array $args): void
    {
        $this->getAWSClient()->Unsubscribe($args);
    }

    public function listSubscriptionsByTopic(array $args): ListSubscriptionsByTopicResponse
    {
        return $this->getAWSClient()->ListSubscriptionsByTopic($args);
    }

    public function getAWSClient(): AwsSnsClient
    {
        if ($this->client) {
            return $this->client;
        }

        $client = $this->inputClient;
        if (is_callable($client)) {
            $client = $client();
        }

        if ($client instanceof AwsSnsClient) {
            return $this->client = $client;
        }

        throw new \LogicException(sprintf(
            'The input client must be an instance of "%s" or a callable that returns it. Got "%s"',
            AwsSnsClient::class,
            is_object($client) ? get_class($client) : gettype($client)
        ));
    }
}
