<?php

declare(strict_types=1);

namespace Enqueue\SnsQs;

use Enqueue\Sns\SnsContext;
use Enqueue\Sns\SnsSubscribe;
use Enqueue\Sns\SnsUnsubscribe;
use Enqueue\Sqs\SqsContext;
use Interop\Queue\Consumer;
use Interop\Queue\Context;
use Interop\Queue\Destination;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Exception\SubscriptionConsumerNotSupportedException;
use Interop\Queue\Exception\TemporaryQueueNotSupportedException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;

class SnsQsContext implements Context
{
    /**
     * @var SnsContext
     */
    private $snsContext;

    /**
     * @var callable
     */
    private $snsContextFactory;

    /**
     * @var SqsContext
     */
    private $sqsContext;

    /**
     * @var callable
     */
    private $sqsContextFactory;

    /**
     * @param SnsContext|callable $snsContext
     * @param SqsContext|callable $sqsContext
     */
    public function __construct($snsContext, $sqsContext)
    {
        if ($snsContext instanceof SnsContext) {
            $this->snsContext = $snsContext;
        } elseif (is_callable($snsContext)) {
            $this->snsContextFactory = $snsContext;
        } else {
            throw new \InvalidArgumentException(sprintf('The $snsContext argument must be either %s or callable that returns %s once called.', SnsContext::class, SnsContext::class));
        }

        if ($sqsContext instanceof SqsContext) {
            $this->sqsContext = $sqsContext;
        } elseif (is_callable($sqsContext)) {
            $this->sqsContextFactory = $sqsContext;
        } else {
            throw new \InvalidArgumentException(sprintf('The $sqsContext argument must be either %s or callable that returns %s once called.', SqsContext::class, SqsContext::class));
        }
    }

    /**
     * @return SnsQsMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        return new SnsQsMessage($body, $properties, $headers);
    }

    /**
     * @return SnsQsTopic
     */
    public function createTopic(string $topicName): Topic
    {
        return new SnsQsTopic($topicName);
    }

    /**
     * @return SnsQsQueue
     */
    public function createQueue(string $queueName): Queue
    {
        return new SnsQsQueue($queueName);
    }

    public function createTemporaryQueue(): Queue
    {
        throw TemporaryQueueNotSupportedException::providerDoestNotSupportIt();
    }

    public function createProducer(): Producer
    {
        return new SnsQsProducer($this->getSnsContext(), $this->getSqsContext());
    }

    /**
     * @param SnsQsQueue $destination
     */
    public function createConsumer(Destination $destination): Consumer
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, SnsQsQueue::class);

        return new SnsQsConsumer($this, $this->getSqsContext()->createConsumer($destination), $destination);
    }

    /**
     * @param SnsQsQueue $queue
     */
    public function purgeQueue(Queue $queue): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, SnsQsQueue::class);

        $this->getSqsContext()->purgeQueue($queue);
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        throw SubscriptionConsumerNotSupportedException::providerDoestNotSupportIt();
    }

    public function declareTopic(SnsQsTopic $topic): void
    {
        $this->getSnsContext()->declareTopic($topic);
    }

    public function setTopicArn(SnsQsTopic $topic, string $arn): void
    {
        $this->getSnsContext()->setTopicArn($topic);
    }

    public function deleteTopic(SnsQsTopic $topic): void
    {
        $this->getSnsContext()->deleteTopic($topic);
    }

    public function declareQueue(SnsQsQueue $queue): void
    {
        $this->getSqsContext()->declareQueue($queue);
    }

    public function deleteQueue(SnsQsQueue $queue): void
    {
        $this->getSqsContext()->deleteQueue($queue);
    }

    public function bind(SnsQsTopic $topic, SnsQsQueue $queue): void
    {
        $this->getSnsContext()->subscribe(new SnsSubscribe(
            $topic,
            $this->getSqsContext()->getQueueArn($queue),
            SnsSubscribe::PROTOCOL_SQS
        ));
    }

    public function unbind(SnsQsTopic $topic, SnsQsQueue $queue): void
    {
        $this->getSnsContext()->unsubscibe(new SnsUnsubscribe(
            $topic,
            $this->getSqsContext()->getQueueArn($queue),
            SnsSubscribe::PROTOCOL_SQS
        ));
    }

    public function close(): void
    {
        $this->getSnsContext()->close();
        $this->getSqsContext()->close();
    }

    private function getSnsContext(): SnsContext
    {
        if (null === $this->snsContext) {
            $context = call_user_func($this->snsContextFactory);
            if (false == $context instanceof SnsContext) {
                throw new \LogicException(sprintf('The factory must return instance of %s. It returned %s', SnsContext::class, is_object($context) ? get_class($context) : gettype($context)));
            }

            $this->snsContext = $context;
        }

        return $this->snsContext;
    }

    private function getSqsContext(): SqsContext
    {
        if (null === $this->sqsContext) {
            $context = call_user_func($this->sqsContextFactory);
            if (false == $context instanceof SqsContext) {
                throw new \LogicException(sprintf('The factory must return instance of %s. It returned %s', SqsContext::class, is_object($context) ? get_class($context) : gettype($context)));
            }

            $this->sqsContext = $context;
        }

        return $this->sqsContext;
    }
}
