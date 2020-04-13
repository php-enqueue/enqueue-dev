<?php

declare(strict_types=1);

namespace Enqueue\Sns;

use Aws\Sns\SnsClient as AwsSnsClient;
use AsyncAws\Sns\ValueObject\Subscription;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\PurgeQueueNotSupportedException;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;

class SnsContext implements Context
{
    /**
     * @var SnsClient|SnsAsyncClient
     */
    private $client;

    /**
     * @var array
     */
    private $config;

    private $topicArns;

    /**
     * @param SnsClient|SnsAsyncClient $client
     */
    public function __construct($client, array $config)
    {
        if ($client instanceof SnsClient) {
            @trigger_error(sprintf('Using a "%s" in "%s" is deprecated since 0.10, use "%s" instead.', SnsClient::class, __CLASS__, SnsAsyncClient::class), E_USER_DEPRECATED);
        }

        $this->client = $client;
        $this->config = $config;

        $this->topicArns = [];
    }

    /**
     * @return SnsMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        return new SnsMessage($body, $properties, $headers);
    }

    /**
     * @return SnsDestination
     */
    public function createTopic(string $topicName): Topic
    {
        return new SnsDestination($topicName);
    }

    /**
     * @return SnsDestination
     */
    public function createQueue(string $queueName): Queue
    {
        return new SnsDestination($queueName);
    }

    public function declareTopic(SnsDestination $destination): void
    {
        $result = $this->client->createTopic([
            'Attributes' => $destination->getAttributes(),
            'Name' => $destination->getQueueName(),
        ]);

        if ($this->client instanceof SnsAsyncClient) {
            $this->topicArns[$destination->getTopicName()] = $result->getTopicArn();
        }

        // @todo in 0.11 remove below code
        if (false == $result->hasKey('TopicArn')) {
            throw new \RuntimeException(sprintf('Cannot create topic. topicName: "%s"', $destination->getTopicName()));
        }

        $this->topicArns[$destination->getTopicName()] = (string) $result->get('TopicArn');
    }

    public function deleteTopic(SnsDestination $destination): void
    {
        $this->client->deleteTopic($this->getTopicArn($destination));

        unset($this->topicArns[$destination->getTopicName()]);
    }

    public function subscribe(SnsSubscribe $subscribe): void
    {
        if ($this->client instanceof SnsAsyncClient) {
            foreach ($this->getSubscriptions($subscribe->getTopic()) as $subscription) {
                if ($subscription->getProtocol() === $subscribe->getProtocol()
                    && $subscription->getEndpoint() === $subscribe->getEndpoint()) {
                    return;
                }
            }
        } else {
            foreach ($this->getSubscriptions($subscribe->getTopic()) as $subscription) {
                if ($subscription['Protocol'] === $subscribe->getProtocol()
                    && $subscription['Endpoint'] === $subscribe->getEndpoint()) {
                    return;
                }
            }
        }

        $this->client->subscribe([
            'Attributes' => $subscribe->getAttributes(),
            'Endpoint' => $subscribe->getEndpoint(),
            'Protocol' => $subscribe->getProtocol(),
            'ReturnSubscriptionArn' => $subscribe->isReturnSubscriptionArn(),
            'TopicArn' => $this->getTopicArn($subscribe->getTopic()),
        ]);
    }

    public function unsubscribe(SnsUnsubscribe $unsubscribe): void
    {
        if ($this->client instanceof SnsAsyncClient) {
            foreach ($this->getSubscriptions($unsubscribe->getTopic()) as $subscription) {
                if ($subscription->getProtocol() !== $unsubscribe->getProtocol()) {
                    continue;
                }

                if ($subscription->getEndpoint() !== $unsubscribe->getEndpoint()) {
                    continue;
                }

                $this->client->unsubscribe([
                    'SubscriptionArn' => $subscription->getSubscriptionArn(),
                ]);
            }

            return;
        }

        // @todo in 0.11 remove below code
        foreach ($this->getSubscriptions($unsubscribe->getTopic()) as $subscription) {
            if ($subscription['Protocol'] != $unsubscribe->getProtocol()) {
                continue;
            }

            if ($subscription['Endpoint'] != $unsubscribe->getEndpoint()) {
                continue;
            }

            $this->client->unsubscribe([
                'SubscriptionArn' => $subscription['SubscriptionArn'],
            ]);
        }
    }

    /**
     * @todo in 0.11 remove typehint "array[]"
     *
     * @return array[]|Subscription[]
     */
    public function getSubscriptions(SnsDestination $destination): iterable
    {
        if ($this->client instanceof SnsAsyncClient) {
            return $this->client->listSubscriptionsByTopic(['TopicArn' => $this->getTopicArn($destination)]);
        }

        // @todo in 0.11 remove below code
        $args = [
            'TopicArn' => $this->getTopicArn($destination),
        ];

        $subscriptions = [];
        while (true) {
            $result = $this->client->listSubscriptionsByTopic($args);

            $subscriptions = array_merge($subscriptions, $result->get('Subscriptions'));

            if (false == $result->hasKey('NextToken')) {
                break;
            }

            $args['NextToken'] = $result->get('NextToken');
        }

        return $subscriptions;
    }

    public function getTopicArn(SnsDestination $destination): string
    {
        if (!array_key_exists($destination->getTopicName(), $this->topicArns)) {
            $this->declareTopic($destination);
        }

        return $this->topicArns[$destination->getTopicName()];
    }

    public function createTemporaryQueue(): Queue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @return SnsProducer
     */
    public function createProducer(): Producer
    {
        return new SnsProducer($this);
    }

    /**
     * @param SnsDestination $destination
     */
    public function createConsumer(Destination $destination): Consumer
    {
        throw new \LogicException('SNS transport does not support consumption. You should consider using SQS instead.');
    }

    public function close(): void
    {
    }

    /**
     * @param SnsDestination $queue
     */
    public function purgeQueue(Queue $queue): void
    {
        PurgeQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    /**
     * @deprecated
     */
    public function getAwsSnsClient(): AwsSnsClient
    {
        @trigger_error('The method is deprecated since 0.10. Do not use pkg\'s internal dependencies.', E_USER_DEPRECATED);

        if (!$this->client instanceof SnsClient) {
            throw new \InvalidArgumentException(sprintf('The injected client in "%s" is a "%s", can not provide a "%s".', __CLASS__, \get_class($this->client), AwsSnsClient::class));
        }

        return $this->client->getAWSClient();
    }

    /**
     * @internal
     *
     * @return SnsAsyncClient|SnsClient
     */
    public function getSnsClient()
    {
        return $this->client;
    }
}
