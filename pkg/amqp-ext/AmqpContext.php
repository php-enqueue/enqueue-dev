<?php

namespace Enqueue\AmqpExt;

use Enqueue\Psr\Context;
use Enqueue\Psr\Destination;
use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\Queue;
use Enqueue\Psr\Topic;

class AmqpContext implements Context
{
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

        $this->buffer = new Buffer();
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpMessage
     */
    public function createMessage($body = '', array $properties = [], array $headers = [])
    {
        return new AmqpMessage($body, $properties, $headers);
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpTopic
     */
    public function createTopic($topicName)
    {
        return new AmqpTopic($topicName);
    }

    /**
     * @param AmqpTopic|Destination $destination
     */
    public function deleteTopic(Destination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpTopic::class);

        $extExchange = new \AMQPExchange($this->getExtChannel());
        $extExchange->delete($destination->getTopicName(), $destination->getFlags());
    }

    /**
     * @param AmqpTopic|Destination $destination
     */
    public function declareTopic(Destination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpTopic::class);

        $extExchange = new \AMQPExchange($this->getExtChannel());
        $extExchange->setName($destination->getTopicName());
        $extExchange->setType($destination->getType());
        $extExchange->setArguments($destination->getArguments());
        $extExchange->setFlags($destination->getFlags());

        $extExchange->declareExchange();
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpQueue
     */
    public function createQueue($queueName)
    {
        return new AmqpQueue($queueName);
    }

    /**
     * @param AmqpQueue|Destination $destination
     */
    public function deleteQueue(Destination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpQueue::class);

        $extQueue = new \AMQPQueue($this->getExtChannel());
        $extQueue->setName($destination->getQueueName());
        $extQueue->delete($destination->getFlags());
    }

    /**
     * @param AmqpQueue|Destination $destination
     */
    public function declareQueue(Destination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpQueue::class);

        $extQueue = new \AMQPQueue($this->getExtChannel());
        $extQueue->setFlags($destination->getFlags());
        $extQueue->setArguments($destination->getArguments());

        if ($destination->getQueueName()) {
            $extQueue->setName($destination->getQueueName());
        }

        $extQueue->declareQueue();

        if (false == $destination->getQueueName()) {
            $destination->setQueueName($extQueue->getName());
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpQueue
     */
    public function createTemporaryQueue()
    {
        $queue = $this->createQueue(null);
        $queue->addFlag(AMQP_EXCLUSIVE);

        $this->declareQueue($queue);

        return $queue;
    }

    /**
     * {@inheritdoc}
     *
     * @return AmqpProducer
     */
    public function createProducer()
    {
        return new AmqpProducer($this->getExtChannel());
    }

    /**
     * {@inheritdoc}
     *
     * @param Destination|AmqpQueue $destination
     *
     * @return AmqpConsumer
     */
    public function createConsumer(Destination $destination)
    {
        $destination instanceof Topic
            ? InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpTopic::class)
            : InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpQueue::class)
        ;

        if ($destination instanceof AmqpTopic) {
            $queue = $this->createTemporaryQueue();
            $this->bind($destination, $queue);

            return new AmqpConsumer($this, $queue, $this->buffer);
        }

        return new AmqpConsumer($this, $destination, $this->buffer);
    }

    public function close()
    {
        $extConnection = $this->getExtChannel()->getConnection();
        if ($extConnection->isConnected()) {
            $extConnection->isPersistent() ? $extConnection->pdisconnect() : $extConnection->disconnect();
        }
    }

    /**
     * @param AmqpTopic|Destination $source
     * @param AmqpQueue|Destination $target
     */
    public function bind(Destination $source, Destination $target)
    {
        InvalidDestinationException::assertDestinationInstanceOf($source, AmqpTopic::class);
        InvalidDestinationException::assertDestinationInstanceOf($target, AmqpQueue::class);

        $amqpQueue = new \AMQPQueue($this->getExtChannel());
        $amqpQueue->setName($target->getQueueName());
        $amqpQueue->bind($source->getTopicName(), $amqpQueue->getName(), $target->getBindArguments());
    }

    /**
     * @return \AMQPConnection
     */
    public function getExtConnection()
    {
        return $this->getExtChannel()->getConnection();
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
     * Purge all messages from the given queue.
     *
     * @param Queue $queue
     */
    public function purge(Queue $queue)
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, AmqpQueue::class);

        $amqpQueue = new \AMQPQueue($this->getExtChannel());
        $amqpQueue->setName($queue->getQueueName());
        $amqpQueue->purge();
    }
}
