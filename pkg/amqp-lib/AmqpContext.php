<?php

declare(strict_types=1);

namespace Enqueue\AmqpLib;

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
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AbstractConnection;
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

    public function __construct(AbstractConnection $connection, array $config)
    {
        $this->config = array_replace([
            'qos_prefetch_size' => 0,
            'qos_prefetch_count' => 1,
            'qos_global' => false,
        ], $config);

        $this->connection = $connection;
    }

    /**
     * @return InteropAmqpMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): Message
    {
        return new AmqpMessage($body, $properties, $headers);
    }

    /**
     * @return InteropAmqpQueue
     */
    public function createQueue(string $name): Queue
    {
        return new AmqpQueue($name);
    }

    /**
     * @return InteropAmqpTopic
     */
    public function createTopic(string $name): Topic
    {
        return new AmqpTopic($name);
    }

    /**
     * @param InteropAmqpTopic|InteropAmqpQueue $destination
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

    /**
     * @return AmqpSubscriptionConsumer
     */
    public function createSubscriptionConsumer(): SubscriptionConsumer
    {
        return new AmqpSubscriptionConsumer($this, (bool) $this->config['heartbeat_on_tick']);
    }

    /**
     * @return AmqpProducer
     */
    public function createProducer(): Producer
    {
        $producer = new AmqpProducer($this->getLibChannel(), $this);
        $producer->setDelayStrategy($this->delayStrategy);

        return $producer;
    }

    /**
     * @return InteropAmqpQueue
     */
    public function createTemporaryQueue(): Queue
    {
        list($name) = $this->getLibChannel()->queue_declare('', false, false, true, false);

        $queue = $this->createQueue($name);
        $queue->addFlag(InteropAmqpQueue::FLAG_EXCLUSIVE);

        return $queue;
    }

    public function declareTopic(InteropAmqpTopic $topic): void
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

    public function deleteTopic(InteropAmqpTopic $topic): void
    {
        $this->getLibChannel()->exchange_delete(
            $topic->getTopicName(),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_IFUNUSED),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_NOWAIT)
        );
    }

    public function declareQueue(InteropAmqpQueue $queue): int
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

    public function deleteQueue(InteropAmqpQueue $queue): void
    {
        $this->getLibChannel()->queue_delete(
            $queue->getQueueName(),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_IFUNUSED),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_IFEMPTY),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_NOWAIT)
        );
    }

    /**
     * @param AmqpQueue $queue
     */
    public function purgeQueue(Queue $queue): void
    {
        InvalidDestinationException::assertDestinationInstanceOf($queue, InteropAmqpQueue::class);

        $this->getLibChannel()->queue_purge(
            $queue->getQueueName(),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_NOWAIT)
        );
    }

    public function bind(InteropAmqpBind $bind): void
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

    public function unbind(InteropAmqpBind $bind): void
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

    public function close(): void
    {
        if ($this->channel) {
            $this->channel->close();
        }
    }

    public function setQos(int $prefetchSize, int $prefetchCount, bool $global): void
    {
        $this->getLibChannel()->basic_qos($prefetchSize, $prefetchCount, $global);
    }

    public function getLibChannel(): AMQPChannel
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
     */
    public function convertMessage(LibAMQPMessage $amqpMessage): InteropAmqpMessage
    {
        $headers = new AMQPTable($amqpMessage->get_properties());
        $headers = $headers->getNativeData();

        $properties = [];
        if (isset($headers['application_headers'])) {
            $properties = $headers['application_headers'];
        }
        unset($headers['application_headers']);

        $message = new AmqpMessage($amqpMessage->getBody(), $properties, $headers);
        $message->setDeliveryTag((int) $amqpMessage->delivery_info['delivery_tag']);
        $message->setRedelivered($amqpMessage->delivery_info['redelivered']);
        $message->setRoutingKey($amqpMessage->delivery_info['routing_key']);

        return $message;
    }
}
