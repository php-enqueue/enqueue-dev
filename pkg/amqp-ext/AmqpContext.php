<?php
namespace Enqueue\AmqpExt;

use Enqueue\Psr\Context;
use Enqueue\Psr\Destination;
use Enqueue\Psr\InvalidDestinationException;
use Enqueue\Psr\Topic;

class AmqpContext implements Context
{
    /**
     * @var \AMQPChannel
     */
    private $extChannel;

    /**
     * @param \AMQPChannel $extChannel
     */
    public function __construct(\AMQPChannel $extChannel)
    {
        $this->extChannel = $extChannel;
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

        $extExchange = new \AMQPExchange($this->extChannel);
        $extExchange->delete($destination->getTopicName(), $destination->getFlags());
    }

    /**
     * @param AmqpTopic|Destination $destination
     */
    public function declareTopic(Destination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpTopic::class);

        $extExchange = new \AMQPExchange($this->extChannel);
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

        $extQueue = new \AMQPQueue($this->extChannel);
        $extQueue->setName($destination->getQueueName());
        $extQueue->delete($destination->getFlags());
    }

    /**
     * @param AmqpQueue|Destination $destination
     */
    public function declareQueue(Destination $destination)
    {
        InvalidDestinationException::assertDestinationInstanceOf($destination, AmqpQueue::class);

        $extQueue = new \AMQPQueue($this->extChannel);
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
        return new AmqpProducer($this->extChannel);
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

            return new AmqpConsumer($this, $queue);
        } else {
            return new AmqpConsumer($this, $destination);
        }
    }

    public function close()
    {
        $extConnection = $this->extChannel->getConnection();
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

        $amqpQueue = new \AMQPQueue($this->extChannel);
        $amqpQueue->setName($target->getQueueName());
        $amqpQueue->bind($source->getTopicName(), $amqpQueue->getName(), $target->getBindArguments());
    }

    /**
     * @return \AMQPConnection
     */
    public function getExtConnection()
    {
        return $this->extChannel->getConnection();
    }

    /**
     * @return mixed
     */
    public function getExtChannel()
    {
        return $this->extChannel;
    }
}
