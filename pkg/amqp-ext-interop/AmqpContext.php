<?php

namespace Enqueue\AmqpExtInterop;

use Enqueue\Psr\Exception;
use Interop\Amqp\AmqpContext as InteropAmqpContext;
use Interop\Amqp\AmqpMessage as InteropAmqpMessage;
use Interop\Amqp\AmqpTopic as InteropAmqpTopic;
use Interop\Amqp\AmqpQueue as InteropAmqpQueue;
use Interop\Amqp\AmqpDestination as InteropAmqpDestination;
use Interop\Amqp\AmqpBind as InteropAmqpBind;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;

class AmqpContext implements InteropAmqpContext
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
     * @var string
     */
    private $receiveMethod;

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
        $extExchange->delete($topic->getTopicName(), $this->convertTopicFlags($topic->getFlags()));
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
        $extExchange->setFlags($this->convertTopicFlags($topic->getFlags()));

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
        $extQueue->delete($this->convertQueueFlags($queue->getFlags()));
    }

    /**
     * {@inheritdoc}
     */
    public function declareQueue(InteropAmqpQueue $queue)
    {
        $extQueue = new \AMQPQueue($this->getExtChannel());
        $extQueue->setName($queue->getQueueName());
        $extQueue->setArguments($queue->getArguments());
        $extQueue->setFlags($this->convertQueueFlags($queue->getFlags()));

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
     * @param int $interop
     *
     * @return int
     */
    private function convertMessageFlags($interop)
    {
        $flags = AMQP_NOPARAM;

        if ($interop & InteropAmqpMessage::FLAG_MANDATORY) {
            $flags |= AMQP_MANDATORY;
        }

        if ($interop & InteropAmqpMessage::FLAG_IMMEDIATE) {
            $flags |= AMQP_IMMEDIATE;
        }

        return $flags;
    }

    /**
     * @param int $interop
     *
     * @return int
     */
    private function convertTopicFlags($interop)
    {
        $flags = AMQP_NOPARAM;

        $flags |= $this->convertDestinationFlags($interop);

        if ($interop & InteropAmqpTopic::FLAG_INTERNAL) {
            $flags |= AMQP_INTERNAL;
        }

        return $flags;
    }

    /**
     * @param int $interop
     *
     * @return int
     */
    private function convertQueueFlags($interop)
    {
        $flags = AMQP_NOPARAM;

        $flags |= $this->convertDestinationFlags($interop);

        if ($interop & InteropAmqpQueue::FLAG_EXCLUSIVE) {
            $flags |= AMQP_EXCLUSIVE;
        }

        return $flags;
    }

    /**
     * @param int $interop
     *
     * @return int
     */
    private function convertDestinationFlags($interop)
    {
        $flags = AMQP_NOPARAM;

        if ($interop & InteropAmqpDestination::FLAG_PASSIVE) {
            $flags |= AMQP_PASSIVE;
        }

        if ($interop & InteropAmqpDestination::FLAG_DURABLE) {
            $flags |= AMQP_DURABLE;
        }

        if ($interop & InteropAmqpDestination::FLAG_AUTODELETE) {
            $flags |= AMQP_AUTODELETE;
        }

        if ($interop & InteropAmqpDestination::FLAG_NOWAIT) {
            $flags |= AMQP_NOWAIT;
        }

        return $flags;
    }
}
