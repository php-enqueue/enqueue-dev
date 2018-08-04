<?php

namespace Enqueue\AmqpExt;

use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\DelayStrategyAwareTrait;
use Interop\Amqp\AmqpBind as InteropAmqpBind;
use Interop\Amqp\AmqpConsumer as InteropAmqpConsumer;
use Interop\Amqp\AmqpContext as InteropAmqpContext;
use Interop\Amqp\AmqpQueue as InteropAmqpQueue;
use Interop\Amqp\AmqpTopic as InteropAmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use Interop\Queue\Exception;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrTopic;

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
     * @var Buffer
     */
    private $buffer;

    /**
     * @var string
     */
    private $receiveMethod;

    /**
     * an item contains an array: [AmqpConsumerInterop $consumer, callable $callback];.
     *
     * @var array
     */
    private $subscribers;

    /**
     * Callable must return instance of \AMQPChannel once called.
     *
     * @param \AMQPChannel|callable $extChannel
     * @param string                $receiveMethod
     */
    public function __construct($extChannel, $receiveMethod)
    {
        $this->receiveMethod = $receiveMethod;

        if ($extChannel instanceof \AMQPChannel) {
            $this->extChannel = $extChannel;
        } elseif (is_callable($extChannel)) {
            $this->extChannelFactory = $extChannel;
        } else {
            throw new \InvalidArgumentException('The extChannel argument must be either AMQPChannel or callable that return AMQPChannel.');
        }

        $this->buffer = new Buffer();
        $this->subscribers = [];
    }

    /**
     * {@inheritdoc}
     */
    public function createMessage($body = '', array $properties = [], array $headers = [])
    {
        return new AmqpMessage($body, $properties, $headers);
    }

    /**
     * {@inheritdoc}
     */
    public function createTopic($topicName)
    {
        return new AmqpTopic($topicName);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteTopic(InteropAmqpTopic $topic)
    {
        $extExchange = new \AMQPExchange($this->getExtChannel());
        $extExchange->delete($topic->getTopicName(), Flags::convertTopicFlags($topic->getFlags()));
    }

    /**
     * {@inheritdoc}
     */
    public function declareTopic(InteropAmqpTopic $topic)
    {
        $extExchange = new \AMQPExchange($this->getExtChannel());
        $extExchange->setName($topic->getTopicName());
        $extExchange->setType($topic->getType());
        $extExchange->setArguments($topic->getArguments());
        $extExchange->setFlags(Flags::convertTopicFlags($topic->getFlags()));

        $extExchange->declareExchange();
    }

    /**
     * {@inheritdoc}
     */
    public function createQueue($queueName)
    {
        return new AmqpQueue($queueName);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQueue(InteropAmqpQueue $queue)
    {
        $extQueue = new \AMQPQueue($this->getExtChannel());
        $extQueue->setName($queue->getQueueName());
        $extQueue->delete(Flags::convertQueueFlags($queue->getFlags()));
    }

    /**
     * {@inheritdoc}
     */
    public function declareQueue(InteropAmqpQueue $queue)
    {
        $extQueue = new \AMQPQueue($this->getExtChannel());
        $extQueue->setName($queue->getQueueName());
        $extQueue->setArguments($queue->getArguments());
        $extQueue->setFlags(Flags::convertQueueFlags($queue->getFlags()));

        return $extQueue->declareQueue();
    }

    /**
     * {@inheritdoc}
     */
    public function purgeQueue(InteropAmqpQueue $queue)
    {
        $amqpQueue = new \AMQPQueue($this->getExtChannel());
        $amqpQueue->setName($queue->getQueueName());
        $amqpQueue->purge();
    }

    /**
     * {@inheritdoc}
     */
    public function bind(InteropAmqpBind $bind)
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

    /**
     * {@inheritdoc}
     */
    public function unbind(InteropAmqpBind $bind)
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
     * {@inheritdoc}
     *
     * @return InteropAmqpQueue
     */
    public function createTemporaryQueue()
    {
        $extQueue = new \AMQPQueue($this->getExtChannel());
        $extQueue->setFlags(AMQP_EXCLUSIVE);

        $extQueue->declareQueue();

        $queue = $this->createQueue($extQueue->getName());
        $queue->addFlag(InteropAmqpQueue::FLAG_EXCLUSIVE);

        return $queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpProducer
     */
    public function createProducer()
    {
        $producer = new AmqpProducer($this->getExtChannel(), $this);
        $producer->setDelayStrategy($this->delayStrategy);

        return $producer;
    }

    /**
     * {@inheritdoc}
     *
     * @param PsrDestination|AmqpQueue $destination
     *
     * @return AmqpConsumer
     */
    public function createConsumer(PsrDestination $destination)
    {
        $destination instanceof PsrTopic
            ? InvalidDestinationException::assertDestinationInstanceOf($destination, InteropAmqpTopic::class)
            : InvalidDestinationException::assertDestinationInstanceOf($destination, InteropAmqpQueue::class)
        ;

        if ($destination instanceof AmqpTopic) {
            $queue = $this->createTemporaryQueue();
            $this->bind(new AmqpBind($destination, $queue, $queue->getQueueName()));

            return new AmqpConsumer($this, $queue, $this->buffer, $this->receiveMethod);
        }

        return new AmqpConsumer($this, $destination, $this->buffer, $this->receiveMethod);
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $extConnection = $this->getExtChannel()->getConnection();
        if ($extConnection->isConnected()) {
            $extConnection->isPersistent() ? $extConnection->pdisconnect() : $extConnection->disconnect();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setQos($prefetchSize, $prefetchCount, $global)
    {
        $this->getExtChannel()->qos($prefetchSize, $prefetchCount);
    }

    /**
     * @return \AMQPChannel
     */
    public function getExtChannel()
    {
        if (false == $this->extChannel) {
            $extChannel = call_user_func($this->extChannelFactory);
            if (false == $extChannel instanceof \AMQPChannel) {
                throw new \LogicException(sprintf(
                    'The factory must return instance of AMQPChannel. It returns %s',
                    is_object($extChannel) ? get_class($extChannel) : gettype($extChannel)
                ));
            }

            $this->extChannel = $extChannel;
        }

        return $this->extChannel;
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(InteropAmqpConsumer $consumer, callable $callback)
    {
        if ($consumer->getConsumerTag() && array_key_exists($consumer->getConsumerTag(), $this->subscribers)) {
            return;
        }

        $extQueue = new \AMQPQueue($this->getExtChannel());
        $extQueue->setName($consumer->getQueue()->getQueueName());

        $extQueue->consume(null, Flags::convertConsumerFlags($consumer->getFlags()), $consumer->getConsumerTag());

        $consumerTag = $extQueue->getConsumerTag();
        $consumer->setConsumerTag($consumerTag);
        $this->subscribers[$consumerTag] = [$consumer, $callback, $extQueue];
    }

    /**
     * {@inheritdoc}
     */
    public function unsubscribe(InteropAmqpConsumer $consumer)
    {
        if (false == $consumer->getConsumerTag()) {
            return;
        }

        $consumerTag = $consumer->getConsumerTag();
        $consumer->setConsumerTag(null);

        list($consumer, $callback, $extQueue) = $this->subscribers[$consumerTag];

        $extQueue->cancel($consumerTag);
        unset($this->subscribers[$consumerTag]);
    }

    /**
     * {@inheritdoc}
     */
    public function consume($timeout = 0)
    {
        if (empty($this->subscribers)) {
            throw new \LogicException('There is no subscribers. Consider calling basicConsumeSubscribe before consuming');
        }

        /** @var \AMQPQueue $extQueue */
        $extConnection = $this->getExtChannel()->getConnection();

        $originalTimeout = $extConnection->getReadTimeout();
        try {
            $extConnection->setReadTimeout($timeout / 1000);

            reset($this->subscribers);
            /** @var $consumer AmqpConsumer */
            list($consumer) = current($this->subscribers);

            $extQueue = new \AMQPQueue($this->getExtChannel());
            $extQueue->setName($consumer->getQueue()->getQueueName());
            $extQueue->consume(function (\AMQPEnvelope $extEnvelope, \AMQPQueue $q) use ($originalTimeout, $extConnection) {
                $consumeTimeout = $extConnection->getReadTimeout();
                try {
                    $extConnection->setReadTimeout($originalTimeout);

                    $message = $this->convertMessage($extEnvelope);
                    $message->setConsumerTag($q->getConsumerTag());

                    /**
                     * @var AmqpConsumer
                     * @var callable     $callback
                     */
                    list($consumer, $callback) = $this->subscribers[$q->getConsumerTag()];

                    return call_user_func($callback, $message, $consumer);
                } finally {
                    $extConnection->setReadTimeout($consumeTimeout);
                }
            }, AMQP_JUST_CONSUME);
        } catch (\AMQPQueueException $e) {
            if ('Consumer timeout exceed' == $e->getMessage()) {
                return null;
            }

            throw $e;
        } finally {
            $extConnection->setReadTimeout($originalTimeout);
        }
    }

    /**
     * @internal It must be used here and in the consumer only
     *
     * @param \AMQPEnvelope $extEnvelope
     *
     * @return AmqpMessage
     */
    public function convertMessage(\AMQPEnvelope $extEnvelope)
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
        $message->setDeliveryTag($extEnvelope->getDeliveryTag());
        $message->setRoutingKey($extEnvelope->getRoutingKey());

        return $message;
    }
}
