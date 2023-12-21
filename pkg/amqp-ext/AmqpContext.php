<?php

namespace Enqueue\AmqpExt;

use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\DelayStrategyAwareTrait;
use Interop\Amqp\AmqpBind as InteropAmqpBind;
use Interop\Amqp\AmqpContext as InteropAmqpContext;
use Interop\Amqp\AmqpMessage as InteropAmqpMessage;
use Interop\Amqp\AmqpQueue as InteropAmqpQueue;
use Interop\Amqp\AmqpTopic as InteropAmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use Interop\Queue\Consumer;
use Interop\Queue\Destination;
use Interop\Queue\Exception\Exception;
use Interop\Queue\Exception\InvalidDestinationException;
use Interop\Queue\Message;
use Interop\Queue\Producer;
use Interop\Queue\Queue;
use Interop\Queue\SubscriptionConsumer;
use Interop\Queue\Topic;

class AmqpContext implements InteropAmqpContext, DelayStrategyAware
{
    use DelayStrategyAwareTrait;

    /**
     * @var \AMQPChannel
     */
    private $extChannel;

    /**
     * @var callable
     */
    private $extChannelFactory;

    /**
     * Callable must return instance of \AMQPChannel once called.
     *
     * @param \AMQPChannel|callable $extChannel
     */
    public function __construct($extChannel)
    {
        if ($extChannel instanceof \AMQPChannel) {
            $this->extChannel = $extChannel;
        } elseif (is_callable($extChannel)) {
            $this->extChannelFactory = $extChannel;
        } else {
            throw new \InvalidArgumentException('The extChannel argument must be either AMQPChannel or callable that return AMQPChannel.');
        }
    }

    /**
     * @return InteropAmqpMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        return new AmqpMessage($body, $properties, $headers);
    }

    /**
     * @return InteropAmqpTopic
     */
    public function createTopic(string $topicName): Topic
    {
        return new AmqpTopic($topicName);
    }

    public function deleteTopic(InteropAmqpTopic $topic): void
    {
        $extExchange = new \AMQPExchange($this->getExtChannel());
        $extExchange->delete($topic->getTopicName(), Flags::convertTopicFlags($topic->getFlags()));
    }

    public function declareTopic(InteropAmqpTopic $topic): void
    {
        $extExchange = new \AMQPExchange($this->getExtChannel());
        $extExchange->setName($topic->getTopicName());
        $extExchange->setType($topic->getType());
        $extExchange->setArguments($topic->getArguments());
        $extExchange->setFlags(Flags::convertTopicFlags($topic->getFlags()));

        $extExchange->declareExchange();
    }

    /**
     * @return InteropAmqpQueue
     */
    public function createQueue(string $queueName): Queue
    {
        return new AmqpQueue($queueName);
    }

    public function deleteQueue(InteropAmqpQueue $queue): void
    {
        $extQueue = new \AMQPQueue($this->getExtChannel());
        $extQueue->setName($queue->getQueueName());
        $extQueue->delete(Flags::convertQueueFlags($queue->getFlags()));
    }

    public function declareQueue(InteropAmqpQueue $queue): int
    {
        $extQueue = new \AMQPQueue($this->getExtChannel());
        $extQueue->setName($queue->getQueueName());
        $extQueue->setArguments($queue->getArguments());
        $extQueue->setFlags(Flags::convertQueueFlags($queue->getFlags()));

        return $extQueue->declareQueue();
    }

    /**
     * @param InteropAmqpQueue $queue
     */
    public function purgeQueue(Queue $queue): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, InteropAmqpQueue::class);

        $amqpQueue = new \AMQPQueue($this->getExtChannel());
        $amqpQueue->setName($queue->getQueueName());
        $amqpQueue->purge();
    }

    public function bind(InteropAmqpBind $bind): void
    {
        if ($bind->getSource() instanceof InteropAmqpQueue && $bind->getTarget() instanceof InteropAmqpQueue) {
            throw new Exception('Is not possible to bind queue to queue. It is possible to bind topic to queue or topic to topic');
        }

        // bind exchange to exchange
        if ($bind->getSource() instanceof InteropAmqpTopic && $bind->getTarget() instanceof InteropAmqpTopic) {
            $exchange = new \AMQPExchange($this->getExtChannel());
            $exchange->setName($bind->getSource()->getTopicName());
            $exchange->bind($bind->getTarget()->getTopicName(), $bind->getRoutingKey(), $bind->getArguments());
        // bind queue to exchange
        } elseif ($bind->getSource() instanceof InteropAmqpQueue) {
            $queue = new \AMQPQueue($this->getExtChannel());
            $queue->setName($bind->getSource()->getQueueName());
            $queue->bind($bind->getTarget()->getTopicName(), $bind->getRoutingKey(), $bind->getArguments());
        // bind exchange to queue
        } else {
            $queue = new \AMQPQueue($this->getExtChannel());
            $queue->setName($bind->getTarget()->getQueueName());
            $queue->bind($bind->getSource()->getTopicName(), $bind->getRoutingKey(), $bind->getArguments());
        }
    }

    public function unbind(InteropAmqpBind $bind): void
    {
        if ($bind->getSource() instanceof InteropAmqpQueue && $bind->getTarget() instanceof InteropAmqpQueue) {
            throw new Exception('Is not possible to unbind queue to queue. It is possible to unbind topic from queue or topic from topic');
        }

        // unbind exchange from exchange
        if ($bind->getSource() instanceof InteropAmqpTopic && $bind->getTarget() instanceof InteropAmqpTopic) {
            $exchange = new \AMQPExchange($this->getExtChannel());
            $exchange->setName($bind->getSource()->getTopicName());
            $exchange->unbind($bind->getTarget()->getTopicName(), $bind->getRoutingKey(), $bind->getArguments());
        // unbind queue from exchange
        } elseif ($bind->getSource() instanceof InteropAmqpQueue) {
            $queue = new \AMQPQueue($this->getExtChannel());
            $queue->setName($bind->getSource()->getQueueName());
            $queue->unbind($bind->getTarget()->getTopicName(), $bind->getRoutingKey(), $bind->getArguments());
        // unbind exchange from queue
        } else {
            $queue = new \AMQPQueue($this->getExtChannel());
            $queue->setName($bind->getTarget()->getQueueName());
            $queue->unbind($bind->getSource()->getTopicName(), $bind->getRoutingKey(), $bind->getArguments());
        }
    }

    /**
     * @return InteropAmqpQueue
     */
    public function createTemporaryQueue(): Queue
    {
        $extQueue = new \AMQPQueue($this->getExtChannel());
        $extQueue->setFlags(\AMQP_EXCLUSIVE);

        $extQueue->declareQueue();

        $queue = $this->createQueue($extQueue->getName());
        $queue->addFlag(InteropAmqpQueue::FLAG_EXCLUSIVE);

        return $queue;
    }

    /**
     * @return AmqpProducer
     */
    public function createProducer(): Producer
    {
        $producer = new AmqpProducer($this->getExtChannel(), $this);
        $producer->setDelayStrategy($this->delayStrategy);

        return $producer;
    }

    /**
     * @param InteropAmqpQueue $destination
     *
     * @return AmqpConsumer
     */
    public function createConsumer(Destination $destination): Consumer
    {
        $destination instanceof Topic
            ? InvalidDestinationException::assertDestinationInstanceOf($destination, InteropAmqpTopic::class)
            : InvalidDestinationException::assertDestinationInstanceOf($destination, InteropAmqpQueue::class)
        ;

        if ($destination instanceof AmqpTopic) {
            $queue = $this->createTemporaryQueue();
            $this->bind(new AmqpBind($destination, $queue, $queue->getQueueName()));

            return new AmqpConsumer($this, $queue);
        }

        return new AmqpConsumer($this, $destination);
    }

    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        return new AmqpSubscriptionConsumer($this);
    }

    public function close(): void
    {
        $extConnection = $this->getExtChannel()->getConnection();
        if ($extConnection->isConnected()) {
            $extConnection->isPersistent() ? $extConnection->pdisconnect() : $extConnection->disconnect();
        }
    }

    public function setQos(int $prefetchSize, int $prefetchCount, bool $global): void
    {
        $this->getExtChannel()->qos($prefetchSize, $prefetchCount);
    }

    public function getExtChannel(): \AMQPChannel
    {
        if (false == $this->extChannel) {
            $extChannel = call_user_func($this->extChannelFactory);
            if (false == $extChannel instanceof \AMQPChannel) {
                throw new \LogicException(sprintf('The factory must return instance of AMQPChannel. It returns %s', is_object($extChannel) ? get_class($extChannel) : gettype($extChannel)));
            }

            $this->extChannel = $extChannel;
        }

        return $this->extChannel;
    }

    /**
     * @internal It must be used here and in the consumer only
     */
    public function convertMessage(\AMQPEnvelope $extEnvelope): InteropAmqpMessage
    {
        $message = new AmqpMessage(
            $extEnvelope->getBody(),
            $extEnvelope->getHeaders(),
            [
                'message_id' => $extEnvelope->getMessageId(),
                'correlation_id' => $extEnvelope->getCorrelationId(),
                'app_id' => $extEnvelope->getAppId(),
                'type' => $extEnvelope->getType(),
                'content_encoding' => $extEnvelope->getContentEncoding(),
                'content_type' => $extEnvelope->getContentType(),
                'expiration' => $extEnvelope->getExpiration(),
                'priority' => $extEnvelope->getPriority(),
                'reply_to' => $extEnvelope->getReplyTo(),
                'timestamp' => $extEnvelope->getTimeStamp(),
                'user_id' => $extEnvelope->getUserId(),
            ]
        );
        $message->setRedelivered($extEnvelope->isRedelivery());
        $message->setDeliveryTag((int) $extEnvelope->getDeliveryTag());
        $message->setRoutingKey($extEnvelope->getRoutingKey());

        return $message;
    }
}
