<?php

declare(strict_types=1);

namespace Enqueue\Sqs;

use Aws\MultiRegionClient;
use Aws\Result;
use Aws\Sqs\SqsClient as AwsSqsClient;

class SqsClient
{
    /**
     * @var AwsSqsClient
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
     * @param AwsSqsClient|MultiRegionClient|callable $inputClient
     */
    public function __construct($inputClient)
    {
        $this->inputClient = $inputClient;
    }

    public function deleteMessage(array $args): Result
    {
        return $this->callApi('deleteMessage', $args);
    }

    public function receiveMessage(array $args): Result
    {
        return $this->callApi('receiveMessage', $args);
    }

    public function changeMessageVisibility(array $args): Result
    {
        return $this->callApi('changeMessageVisibility', $args);
    }

    public function purgeQueue(array $args): Result
    {
        return $this->callApi('purgeQueue', $args);
    }

    public function getQueueUrl(array $args): Result
    {
        return $this->callApi('getQueueUrl', $args);
    }

    public function getQueueAttributes(array $args): Result
    {
        return $this->callApi('getQueueAttributes', $args);
    }

    public function createQueue(array $args): Result
    {
        return $this->callApi('createQueue', $args);
    }

    public function deleteQueue(array $args): Result
    {
        return $this->callApi('deleteQueue', $args);
    }

    public function sendMessage(array $args): Result
    {
        return $this->callApi('sendMessage', $args);
    }

    public function getAWSClient(): AwsSqsClient
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
        } elseif ($client instanceof AwsSqsClient) {
            $this->singleClient = $client;

            return;
        } elseif (is_callable($client)) {
            $client = call_user_func($client);
            if ($client instanceof MultiRegionClient) {
                $this->multiClient = $client;

                return;
            }
            if ($client instanceof AwsSqsClient) {
                $this->singleClient = $client;

                return;
            }
        }

        throw new \LogicException(sprintf(
            'The input client must be an instance of "%s" or "%s" or a callable that returns one of those. Got "%s"',
            AwsSqsClient::class,
            MultiRegionClient::class,
            is_object($client) ? get_class($client) : gettype($client)
        ));
    }
}
