<?php

namespace Enqueue\AmqpBunny;

use Bunny\Channel;
use Bunny\Client;
use Bunny\Message;
use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\DelayStrategyAwareTrait;
use Interop\Amqp\AmqpBind as InteropAmqpBind;
use Interop\Amqp\AmqpConsumer as InteropAmqpConsumer;
use Interop\Amqp\AmqpContext as InteropAmqpContext;
use Interop\Amqp\AmqpMessage as InteropAmqpMessage;
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
     * @var Channel
     */
    private $bunnyChannel;

    /**
     * @var callable
     */
    private $bunnyChannelFactory;

    /**
     * @var string
     */
    private $config;

    /**
     * @var Buffer
     */
    private $buffer;

    /**
     * an item contains an array: [AmqpConsumerInterop $consumer, callable $callback];.
     *
     * @var array
     */
    private $subscribers;

    /**
     * Callable must return instance of \Bunny\Channel once called.
     *
     * @param Channel|callable $bunnyChannel
     * @param array            $config
     */
    public function __construct($bunnyChannel, $config = [])
    {
        $this->config = array_replace([
            'receive_method' => 'basic_get',
            'qos_prefetch_size' => 0,
            'qos_prefetch_count' => 1,
            'qos_global' => false,
        ], $config);

        if ($bunnyChannel instanceof Channel) {
            $this->bunnyChannel = $bunnyChannel;
        } elseif (is_callable($bunnyChannel)) {
            $this->bunnyChannelFactory = $bunnyChannel;
        } else {
            throw new \InvalidArgumentException('The bunnyChannel argument must be either \Bunny\Channel or callable that return it.');
        }

        $this->buffer = new Buffer();
    }

    /**
     * @param string|null $body
     * @param array       $properties
     * @param array       $headers
     *
     * @return InteropAmqpMessage
     */
    public function createMessage($body = '', array $properties = [], array $headers = [])
    {
        return new AmqpMessage($body, $properties, $headers);
    }

    /**
     * @param string $name
     *
     * @return InteropAmqpQueue
     */
    public function createQueue($name)
    {
        return new AmqpQueue($name);
    }

    /**
     * @param string $name
     *
     * @return InteropAmqpTopic
     */
    public function createTopic($name)
    {
        return new AmqpTopic($name);
    }

    /**
     * @param PsrDestination $destination
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

            return new AmqpConsumer($this->getBunnyChannel(), $queue, $this->buffer, $this->config['receive_method']);
        }

        return new AmqpConsumer($this->getBunnyChannel(), $destination, $this->buffer, $this->config['receive_method']);
    }

    /**
     * @return AmqpProducer
     */
    public function createProducer()
    {
        $producer = new AmqpProducer($this->getBunnyChannel(), $this);
        $producer->setDelayStrategy($this->delayStrategy);

        return $producer;
    }

    /**
     * @return InteropAmqpQueue
     */
    public function createTemporaryQueue()
    {
        $frame = $this->getBunnyChannel()->queueDeclare('', false, false, true, false);

        $queue = $this->createQueue($frame->queue);
        $queue->addFlag(InteropAmqpQueue::FLAG_EXCLUSIVE);

        return $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function declareTopic(InteropAmqpTopic $topic)
    {
        $this->getBunnyChannel()->exchangeDeclare(
            $topic->getTopicName(),
            $topic->getType(),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_PASSIVE),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_DURABLE),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_AUTODELETE),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_INTERNAL),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_NOWAIT),
            $topic->getArguments()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteTopic(InteropAmqpTopic $topic)
    {
        $this->getBunnyChannel()->exchangeDelete(
            $topic->getTopicName(),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_IFUNUSED),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_NOWAIT)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function declareQueue(InteropAmqpQueue $queue)
    {
        $frame = $this->getBunnyChannel()->queueDeclare(
            $queue->getQueueName(),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_PASSIVE),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_DURABLE),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_EXCLUSIVE),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_AUTODELETE),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_NOWAIT),
            $queue->getArguments()
        );

        return $frame->messageCount;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQueue(InteropAmqpQueue $queue)
    {
        $this->getBunnyChannel()->queueDelete(
            $queue->getQueueName(),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_IFUNUSED),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_IFEMPTY),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_NOWAIT)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function purgeQueue(InteropAmqpQueue $queue)
    {
        $this->getBunnyChannel()->queuePurge(
            $queue->getQueueName(),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_NOWAIT)
        );
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
            $this->getBunnyChannel()->exchangeBind(
                $bind->getTarget()->getTopicName(),
                $bind->getSource()->getTopicName(),
                $bind->getRoutingKey(),
                (bool) ($bind->getFlags() & InteropAmqpBind::FLAG_NOWAIT),
                $bind->getArguments()
            );
            // bind queue to exchange
        } elseif ($bind->getSource() instanceof InteropAmqpQueue) {
            $this->getBunnyChannel()->queueBind(
                $bind->getSource()->getQueueName(),
                $bind->getTarget()->getTopicName(),
                $bind->getRoutingKey(),
                (bool) ($bind->getFlags() & InteropAmqpBind::FLAG_NOWAIT),
                $bind->getArguments()
            );
            // bind exchange to queue
        } else {
            $this->getBunnyChannel()->queueBind(
                $bind->getTarget()->getQueueName(),
                $bind->getSource()->getTopicName(),
                $bind->getRoutingKey(),
                (bool) ($bind->getFlags() & InteropAmqpBind::FLAG_NOWAIT),
                $bind->getArguments()
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function unbind(InteropAmqpBind $bind)
    {
        if ($bind->getSource() instanceof InteropAmqpQueue && $bind->getTarget() instanceof InteropAmqpQueue) {
            throw new Exception('Is not possible to bind queue to queue. It is possible to bind topic to queue or topic to topic');
        }

        // bind exchange to exchange
        if ($bind->getSource() instanceof InteropAmqpTopic && $bind->getTarget() instanceof InteropAmqpTopic) {
            $this->getBunnyChannel()->exchangeUnbind(
                $bind->getTarget()->getTopicName(),
                $bind->getSource()->getTopicName(),
                $bind->getRoutingKey(),
                (bool) ($bind->getFlags() & InteropAmqpBind::FLAG_NOWAIT),
                $bind->getArguments()
            );
            // bind queue to exchange
        } elseif ($bind->getSource() instanceof InteropAmqpQueue) {
            $this->getBunnyChannel()->queueUnbind(
                $bind->getSource()->getQueueName(),
                $bind->getTarget()->getTopicName(),
                $bind->getRoutingKey(),
                $bind->getArguments()
            );
            // bind exchange to queue
        } else {
            $this->getBunnyChannel()->queueUnbind(
                $bind->getTarget()->getQueueName(),
                $bind->getSource()->getTopicName(),
                $bind->getRoutingKey(),
                $bind->getArguments()
            );
        }
    }

    public function close()
    {
        if ($this->bunnyChannel) {
            $this->bunnyChannel->close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setQos($prefetchSize, $prefetchCount, $global)
    {
        $this->getBunnyChannel()->qos($prefetchSize, $prefetchCount, $global);
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(InteropAmqpConsumer $consumer, callable $callback)
    {
        if ($consumer->getConsumerTag() && array_key_exists($consumer->getConsumerTag(), $this->subscribers)) {
            return;
        }

        $bunnyCallback = function (Message $message, Channel $channel, Client $bunny) {
            $receivedMessage = $this->convertMessage($message);
            $receivedMessage->setConsumerTag($message->consumerTag);

            /**
             * @var AmqpConsumer
             * @var callable     $callback
             */
            list($consumer, $callback) = $this->subscribers[$message->consumerTag];

            if (false === call_user_func($callback, $receivedMessage, $consumer)) {
                $bunny->stop();
            }
        };

        $frame = $this->getBunnyChannel()->consume(
            $bunnyCallback,
            $consumer->getQueue()->getQueueName(),
            $consumer->getConsumerTag(),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_NOLOCAL),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_NOACK),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_EXCLUSIVE),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_NOWAIT)
        );

        if (empty($frame->consumerTag)) {
            throw new Exception('Got empty consumer tag');
        }

        $consumer->setConsumerTag($frame->consumerTag);

        $this->subscribers[$frame->consumerTag] = [$consumer, $callback];
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

        $this->getBunnyChannel()->cancel($consumerTag);
        $consumer->setConsumerTag(null);
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

        $this->getBunnyChannel()->getClient()->run($timeout / 1000);
    }

    /**
     * @return Channel
     */
    public function getBunnyChannel()
    {
        if (false == $this->bunnyChannel) {
            $bunnyChannel = call_user_func($this->bunnyChannelFactory);
            if (false == $bunnyChannel instanceof Channel) {
                throw new \LogicException(sprintf(
                    'The factory must return instance of \Bunny\Channel. It returned %s',
                    is_object($bunnyChannel) ? get_class($bunnyChannel) : gettype($bunnyChannel)
                ));
            }

            $this->bunnyChannel = $bunnyChannel;
        }

        return $this->bunnyChannel;
    }

    /**
     * @param Message $bunnyMessage
     *
     * @return InteropAmqpMessage
     */
    private function convertMessage(Message $bunnyMessage)
    {
        $headers = $bunnyMessage->headers;

        $properties = [];
        if (isset($headers['application_headers'])) {
            $properties = $headers['application_headers'];
        }
        unset($headers['application_headers']);

        $message = new AmqpMessage($bunnyMessage->content, $properties, $headers);
        $message->setDeliveryTag($bunnyMessage->deliveryTag);
        $message->setRedelivered($bunnyMessage->redelivered);
        $message->setRoutingKey($bunnyMessage->routingKey);

        return $message;
    }
}
