<?php

namespace Enqueue\AmqpLib;

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
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage as LibAMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpContext implements InteropAmqpContext, DelayStrategyAware
{
    use DelayStrategyAwareTrait;

    /**
     * @var AbstractConnection
     */
    private $connection;

    /**
     * @var AMQPChannel
     */
    private $channel;

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
     * @param AbstractConnection $connection
     * @param array              $config
     */
    public function __construct(AbstractConnection $connection, $config = [])
    {
        $this->config = array_replace([
            'receive_method' => 'basic_get',
            'qos_prefetch_size' => 0,
            'qos_prefetch_count' => 1,
            'qos_global' => false,
        ], $config);

        $this->connection = $connection;
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

            return new AmqpConsumer($this, $queue, $this->buffer, $this->config['receive_method']);
        }

        return new AmqpConsumer($this, $destination, $this->buffer, $this->config['receive_method']);
    }

    /**
     * @return AmqpProducer
     */
    public function createProducer()
    {
        $producer = new AmqpProducer($this->getLibChannel(), $this);
        $producer->setDelayStrategy($this->delayStrategy);

        return $producer;
    }

    /**
     * @return InteropAmqpQueue
     */
    public function createTemporaryQueue()
    {
        list($name) = $this->getLibChannel()->queue_declare('', false, false, true, false);

        $queue = $this->createQueue($name);
        $queue->addFlag(InteropAmqpQueue::FLAG_EXCLUSIVE);

        return $queue;
    }

    /**
     * {@inheritdoc}
     */
    public function declareTopic(InteropAmqpTopic $topic)
    {
        $this->getLibChannel()->exchange_declare(
            $topic->getTopicName(),
            $topic->getType(),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_PASSIVE),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_DURABLE),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_AUTODELETE),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_INTERNAL),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_NOWAIT),
            $topic->getArguments() ? new AMQPTable($topic->getArguments()) : null
        );
    }

    /**
     * {@inheritdoc}
     */
    public function deleteTopic(InteropAmqpTopic $topic)
    {
        $this->getLibChannel()->exchange_delete(
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
        list(, $messageCount) = $this->getLibChannel()->queue_declare(
            $queue->getQueueName(),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_PASSIVE),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_DURABLE),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_EXCLUSIVE),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_AUTODELETE),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_NOWAIT),
            $queue->getArguments() ? new AMQPTable($queue->getArguments()) : null
        );

        return $messageCount;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteQueue(InteropAmqpQueue $queue)
    {
        $this->getLibChannel()->queue_delete(
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
        $this->getLibChannel()->queue_purge(
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
            $this->getLibChannel()->exchange_bind(
                $bind->getTarget()->getTopicName(),
                $bind->getSource()->getTopicName(),
                $bind->getRoutingKey(),
                (bool) ($bind->getFlags() & InteropAmqpBind::FLAG_NOWAIT),
                $bind->getArguments()
            );
            // bind queue to exchange
        } elseif ($bind->getSource() instanceof InteropAmqpQueue) {
            $this->getLibChannel()->queue_bind(
                $bind->getSource()->getQueueName(),
                $bind->getTarget()->getTopicName(),
                $bind->getRoutingKey(),
                (bool) ($bind->getFlags() & InteropAmqpBind::FLAG_NOWAIT),
                $bind->getArguments()
            );
            // bind exchange to queue
        } else {
            $this->getLibChannel()->queue_bind(
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
            $this->getLibChannel()->exchange_unbind(
                $bind->getTarget()->getTopicName(),
                $bind->getSource()->getTopicName(),
                $bind->getRoutingKey(),
                (bool) ($bind->getFlags() & InteropAmqpBind::FLAG_NOWAIT),
                $bind->getArguments()
            );
            // bind queue to exchange
        } elseif ($bind->getSource() instanceof InteropAmqpQueue) {
            $this->getLibChannel()->queue_unbind(
                $bind->getSource()->getQueueName(),
                $bind->getTarget()->getTopicName(),
                $bind->getRoutingKey(),
                $bind->getArguments()
            );
            // bind exchange to queue
        } else {
            $this->getLibChannel()->queue_unbind(
                $bind->getTarget()->getQueueName(),
                $bind->getSource()->getTopicName(),
                $bind->getRoutingKey(),
                $bind->getArguments()
            );
        }
    }

    public function close()
    {
        if ($this->channel) {
            $this->channel->close();
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setQos($prefetchSize, $prefetchCount, $global)
    {
        $this->getLibChannel()->basic_qos($prefetchSize, $prefetchCount, $global);
    }

    /**
     * {@inheritdoc}
     */
    public function subscribe(InteropAmqpConsumer $consumer, callable $callback)
    {
        if ($consumer->getConsumerTag() && array_key_exists($consumer->getConsumerTag(), $this->subscribers)) {
            return;
        }

        $libCallback = function (LibAMQPMessage $message) {
            $receivedMessage = $this->convertMessage($message);
            $receivedMessage->setConsumerTag($message->delivery_info['consumer_tag']);

            /**
             * @var AmqpConsumer
             * @var callable     $callback
             */
            list($consumer, $callback) = $this->subscribers[$message->delivery_info['consumer_tag']];

            if (false === call_user_func($callback, $receivedMessage, $consumer)) {
                throw new StopBasicConsumptionException();
            }
        };

        $consumerTag = $this->getLibChannel()->basic_consume(
            $consumer->getQueue()->getQueueName(),
            $consumer->getConsumerTag(),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_NOLOCAL),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_NOACK),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_EXCLUSIVE),
            (bool) ($consumer->getFlags() & InteropAmqpConsumer::FLAG_NOWAIT),
            $libCallback
        );

        if (empty($consumerTag)) {
            throw new Exception('Got empty consumer tag');
        }

        $consumer->setConsumerTag($consumerTag);

        $this->subscribers[$consumerTag] = [$consumer, $callback];
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

        $this->getLibChannel()->basic_cancel($consumerTag);

        $consumer->setConsumerTag(null);
        unset($this->subscribers[$consumerTag], $this->getLibChannel()->callbacks[$consumerTag]);
    }

    /**
     * {@inheritdoc}
     */
    public function consume($timeout = 0)
    {
        if (empty($this->subscribers)) {
            throw new \LogicException('There is no subscribers. Consider calling basicConsumeSubscribe before consuming');
        }

        try {
            while (true) {
                $start = microtime(true);

                $this->channel->wait(null, false, $timeout / 1000);

                if ($timeout <= 0) {
                    continue;
                }

                // compute remaining timeout and continue until time is up
                $stop = microtime(true);
                $timeout -= ($stop - $start) * 1000;

                if ($timeout <= 0) {
                    break;
                }
            }
        } catch (AMQPTimeoutException $e) {
        } catch (StopBasicConsumptionException $e) {
        }
    }

    /**
     * @return AMQPChannel
     */
    public function getLibChannel()
    {
        if (null === $this->channel) {
            $this->channel = $this->connection->channel();
            $this->channel->basic_qos(
                $this->config['qos_prefetch_size'],
                $this->config['qos_prefetch_count'],
                $this->config['qos_global']
            );
        }

        return $this->channel;
    }

    /**
     * @internal It must be used here and in the consumer only
     *
     * @param LibAMQPMessage $amqpMessage
     *
     * @return InteropAmqpMessage
     */
    public function convertMessage(LibAMQPMessage $amqpMessage)
    {
        $headers = new AMQPTable($amqpMessage->get_properties());
        $headers = $headers->getNativeData();

        $properties = [];
        if (isset($headers['application_headers'])) {
            $properties = $headers['application_headers'];
        }
        unset($headers['application_headers']);

        $message = new AmqpMessage($amqpMessage->getBody(), $properties, $headers);
        $message->setDeliveryTag($amqpMessage->delivery_info['delivery_tag']);
        $message->setRedelivered($amqpMessage->delivery_info['redelivered']);
        $message->setRoutingKey($amqpMessage->delivery_info['routing_key']);

        return $message;
    }
}
