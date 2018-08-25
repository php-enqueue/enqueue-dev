<?php

declare(strict_types=1);

namespace Enqueue\AmqpBunny;

use Bunny\Channel;
use Bunny\Message;
use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\DelayStrategyAwareTrait;
use Interop\Amqp\AmqpBind as InteropAmqpBind;
use Interop\Amqp\AmqpContext as InteropAmqpContext;
use Interop\Amqp\AmqpDestination;
use Interop\Amqp\AmqpMessage as InteropAmqpMessage;
use Interop\Amqp\AmqpQueue as InteropAmqpQueue;
use Interop\Amqp\AmqpTopic as InteropAmqpTopic;
use Interop\Amqp\Impl\AmqpBind;
use Interop\Amqp\Impl\AmqpMessage;
use Interop\Amqp\Impl\AmqpQueue;
use Interop\Amqp\Impl\AmqpTopic;
use Interop\Queue\Exception;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\PsrConsumer;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrProducer;
use Interop\Queue\PsrQueue;
use Interop\Queue\PsrSubscriptionConsumer;
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
     * Callable must return instance of \Bunny\Channel once called.
     *
     * @param Channel|callable $bunnyChannel
     * @param array            $config
     */
    public function __construct($bunnyChannel, array $config)
    {
        $this->config = array_replace([
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
    }

    /**
     * @return InteropAmqpMessage
     */
    public function createMessage(string $body = '', array $properties = [], array $headers = []): PsrMessage
    {
        return new AmqpMessage($body, $properties, $headers);
    }

    /**
     * @return InteropAmqpQueue
     */
    public function createQueue(string $name): PsrQueue
    {
        return new AmqpQueue($name);
    }

    /**
     * @return InteropAmqpTopic
     */
    public function createTopic(string $name): PsrTopic
    {
        return new AmqpTopic($name);
    }

    /**
     * @param AmqpDestination $destination
     *
     * @return AmqpConsumer
     */
    public function createConsumer(PsrDestination $destination): PsrConsumer
    {
        $destination instanceof PsrTopic
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
    public function createSubscriptionConsumer(): PsrSubscriptionConsumer
    {
        return new AmqpSubscriptionConsumer($this);
    }

    /**
     * @return AmqpProducer
     */
    public function createProducer(): PsrProducer
    {
        $producer = new AmqpProducer($this->getBunnyChannel(), $this);
        $producer->setDelayStrategy($this->delayStrategy);

        return $producer;
    }

    /**
     * @return InteropAmqpQueue
     */
    public function createTemporaryQueue(): PsrQueue
    {
        $frame = $this->getBunnyChannel()->queueDeclare('', false, false, true, false);

        $queue = $this->createQueue($frame->queue);
        $queue->addFlag(InteropAmqpQueue::FLAG_EXCLUSIVE);

        return $queue;
    }

    public function declareTopic(InteropAmqpTopic $topic): void
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

    public function deleteTopic(InteropAmqpTopic $topic): void
    {
        $this->getBunnyChannel()->exchangeDelete(
            $topic->getTopicName(),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_IFUNUSED),
            (bool) ($topic->getFlags() & InteropAmqpTopic::FLAG_NOWAIT)
        );
    }

    public function declareQueue(InteropAmqpQueue $queue): int
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

    public function deleteQueue(InteropAmqpQueue $queue): void
    {
        $this->getBunnyChannel()->queueDelete(
            $queue->getQueueName(),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_IFUNUSED),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_IFEMPTY),
            (bool) ($queue->getFlags() & InteropAmqpQueue::FLAG_NOWAIT)
        );
    }

    /**
     * @param InteropAmqpQueue $queue
     */
    public function purgeQueue(PsrQueue $queue): void
    {
        $this->getBunnyChannel()->queuePurge(
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

    public function unbind(InteropAmqpBind $bind): void
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

    public function close(): void
    {
        if ($this->bunnyChannel) {
            $this->bunnyChannel->close();
        }
    }

    public function setQos(int $prefetchSize, int $prefetchCount, bool $global): void
    {
        $this->getBunnyChannel()->qos($prefetchSize, $prefetchCount, $global);
    }

    public function getBunnyChannel(): Channel
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
     * @internal It must be used here and in the consumer only
     */
    public function convertMessage(Message $bunnyMessage): InteropAmqpMessage
    {
        $headers = $bunnyMessage->headers;

        $properties = [];
        if (isset($headers['application_headers'])) {
            $properties = $headers['application_headers'];
        }
        unset($headers['application_headers']);

        if (array_key_exists('timestamp', $headers) && $headers['timestamp']) {
            /** @var \DateTime $date */
            $date = $headers['timestamp'];

            $headers['timestamp'] = (int) $date->format('U');
        }

        $message = new AmqpMessage($bunnyMessage->content, $properties, $headers);
        $message->setDeliveryTag((string) $bunnyMessage->deliveryTag);
        $message->setRedelivered($bunnyMessage->redelivered);
        $message->setRoutingKey($bunnyMessage->routingKey);

        return $message;
    }
}
