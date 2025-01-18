<?php

declare(strict_types=1);

namespace Enqueue\Sns;

use Aws\MultiRegionClient;
use Aws\Result;
use Aws\Sns\SnsClient as AwsSnsClient;

class SnsClient
{
    /**
     * @var AwsSnsClient
     */
    private $singleClient;

    /**
     * @var MultiRegionClient
     */
    private $multiClient;

    /**
     * @var callable
     */
    private $inputClient;

    /**
     * @param AwsSnsClient|MultiRegionClient|callable $inputClient
     */
    public function __construct($inputClient)
    {
        $this->inputClient = $inputClient;
    }

    public function createTopic(array $args): Result
    {
        return $this->callApi('createTopic', $args);
    }

    public function deleteTopic(string $topicArn): Result
    {
        return $this->callApi('DeleteTopic', [
            'TopicArn' => $topicArn,
        ]);
    }

    public function publish(array $args): Result
    {
        return $this->callApi('publish', $args);
    }

    public function subscribe(array $args): Result
    {
        return $this->callApi('subscribe', $args);
    }

    public function unsubscribe(array $args): Result
    {
        return $this->callApi('unsubscribe', $args);
    }

    public function setSubscriptionAttributes(array $args): Result
    {
        return $this->callApi('setSubscriptionAttributes', $args);
    }

    public function listSubscriptionsByTopic(array $args): Result
    {
        return $this->callApi('ListSubscriptionsByTopic', $args);
    }

    public function getAWSClient(): AwsSnsClient
    {
        $this->resolveClient();

        if ($this->singleClient) {
            return $this->singleClient;
        }

        if ($this->multiClient) {
            $mr = new \ReflectionMethod($this->multiClient, 'getClientFromPool');
            $mr->setAccessible(true);
            $singleClient = $mr->invoke($this->multiClient, $this->multiClient->getRegion());
            $mr->setAccessible(false);

            return $singleClient;
        }

        throw new \LogicException('The multi or single client must be set');
    }

    private function callApi(string $name, array $args): Result
    {
        $this->resolveClient();

        if ($this->singleClient) {
            if (false == empty($args['@region'])) {
                throw new \LogicException('Cannot send message to another region because transport is configured with single aws client');
            }

            unset($args['@region']);

            return call_user_func([$this->singleClient, $name], $args);
        }

        if ($this->multiClient) {
            return call_user_func([$this->multiClient, $name], $args);
        }

        throw new \LogicException('The multi or single client must be set');
    }

    private function resolveClient(): void
    {
        if ($this->singleClient || $this->multiClient) {
            return;
        }

        $client = $this->inputClient;
        if ($client instanceof MultiRegionClient) {
            $this->multiClient = $client;

            return;
        } elseif ($client instanceof AwsSnsClient) {
            $this->singleClient = $client;

            return;
        } elseif (is_callable($client)) {
            $client = call_user_func($client);
            if ($client instanceof MultiRegionClient) {
                $this->multiClient = $client;

                return;
            }
            if ($client instanceof AwsSnsClient) {
                $this->singleClient = $client;

                return;
            }
        }

        throw new \LogicException(sprintf('The input client must be an instance of "%s" or "%s" or a callable that returns one of those. Got "%s"', AwsSnsClient::class, MultiRegionClient::class, is_object($client) ? $client::class : gettype($client)));
    }
}
