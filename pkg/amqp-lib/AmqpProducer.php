<?php

namespace Enqueue\AmqpLib;

use Enqueue\AmqpTools\DelayStrategyAware;
use Enqueue\AmqpTools\DelayStrategyAwareTrait;
use Interop\Amqp\AmqpDestination as InteropAmqpDestination;
use Interop\Amqp\AmqpMessage as InteropAmqpMessage;
use Interop\Amqp\AmqpProducer as InteropAmqpProducer;
use Interop\Amqp\AmqpQueue as InteropAmqpQueue;
use Interop\Amqp\AmqpTopic as InteropAmqpTopic;
use Interop\Queue\DeliveryDelayNotSupportedException;
use Interop\Queue\Exception;
use Interop\Queue\InvalidDestinationException;
use Interop\Queue\InvalidMessageException;
use Interop\Queue\PsrDestination;
use Interop\Queue\PsrMessage;
use Interop\Queue\PsrTopic;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage as LibAMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;

class AmqpProducer implements InteropAmqpProducer, DelayStrategyAware
{
    use DelayStrategyAwareTrait;

    /**
     * @var int|null
     */
    private $priority;

    /**
     * @var int|float|null
     */
    private $timeToLive;

    /**
     * @var int
     */
    private $deliveryDelay;

    /**
     * @var AMQPChannel
     */
    private $channel;

    /**
     * @var AmqpContext
     */
    private $context;

    /**
     * @param AMQPChannel $channel
     * @param AmqpContext $context
     */
    public function __construct(AMQPChannel $channel, AmqpContext $context)
    {
        $this->channel = $channel;
        $this->context = $context;
    }

    /**
     * {@inheritdoc}
     *
     * @param InteropAmqpTopic|InteropAmqpQueue $destination
     * @param InteropAmqpMessage                $message
     */
    public function send(PsrDestination $destination, PsrMessage $message)
    {
        $destination instanceof PsrTopic
            ? InvalidDestinationException::assertDestinationInstanceOf($destination, InteropAmqpTopic::class)
            : InvalidDestinationException::assertDestinationInstanceOf($destination, InteropAmqpQueue::class)
        ;

        InvalidMessageException::assertMessageInstanceOf($message, InteropAmqpMessage::class);

        try {
            $this->doSend($destination, $message);
        } catch (\Exception $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setDeliveryDelay($deliveryDelay)
    {
        if (null === $this->delayStrategy) {
            throw DeliveryDelayNotSupportedException::providerDoestNotSupportIt();
        }

        $this->deliveryDelay = $deliveryDelay;
    }

    /**
     * {@inheritdoc}
     */
    public function getDeliveryDelay()
    {
        return $this->deliveryDelay;
    }

    /**
     * {@inheritdoc}
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * {@inheritdoc}
     */
    public function setTimeToLive($timeToLive)
    {
        $this->timeToLive = $timeToLive;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTimeToLive()
    {
        return $this->timeToLive;
    }

    private function doSend(InteropAmqpDestination $destination, InteropAmqpMessage $message)
    {
        if (null !== $this->priority && null === $message->getPriority()) {
            $message->setPriority($this->priority);
        }

        if (null !== $this->timeToLive && null === $message->getExpiration()) {
            $message->setExpiration($this->timeToLive);
        }

        $amqpProperties = $message->getHeaders();

        if ($appProperties = $message->getProperties()) {
            $amqpProperties['application_headers'] = new AMQPTable($appProperties);
        }

        $amqpMessage = new LibAMQPMessage($message->getBody(), $amqpProperties);

        if ($this->deliveryDelay) {
            $this->delayStrategy->delayMessage($this->context, $destination, $message, $this->deliveryDelay);
        } elseif ($destination instanceof InteropAmqpTopic) {
            $this->channel->basic_publish(
                $amqpMessage,
                $destination->getTopicName(),
                $message->getRoutingKey(),
                (bool) ($message->getFlags() & InteropAmqpMessage::FLAG_MANDATORY),
                (bool) ($message->getFlags() & InteropAmqpMessage::FLAG_IMMEDIATE)
            );
        } else {
            $this->channel->basic_publish(
                $amqpMessage,
                '',
                $destination->getQueueName(),
                (bool) ($message->getFlags() & InteropAmqpMessage::FLAG_MANDATORY),
                (bool) ($message->getFlags() & InteropAmqpMessage::FLAG_IMMEDIATE)
            );
        }
    }
}
